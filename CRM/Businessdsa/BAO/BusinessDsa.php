<?php
/**
 * BAO for Business DSA
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
class CRM_Businessdsa_BAO_BusinessDsa {

  /**
   * Method to sum all non-accountable amounts of active components into base amount
   *
   * @return double $baseAmount
   * @access public
   * @static
   */
  public static function calculateBaseAmount() {
    $baseAmount = 0;
    $component = new CRM_Businessdsa_BAO_Component();
    $component->is_active = 1;
    $component->accountable_advance = 0;
    $component->find();
    while ($component->fetch()) {
      $baseAmount = $baseAmount + $component->dsa_amount;
    }
    return $baseAmount;
  }

  /**
   * Method to sum all accountable amounts of active components into accountable amount
   * @return int
   */
  public static function calculateAccountableAmount() {
    $accountableAmount = 0;
    $component = new CRM_Businessdsa_BAO_Component();
    $component->is_active = 1;
    $component->accountable_advance = 1;
    $component->find();
    while ($component->fetch()) {
      $accountableAmount = $accountableAmount + $component->dsa_amount;
    }
    return $accountableAmount;
  }

  /**
   * Method to create Debit Business DSA activity and custom fields
   *
   * @param array $params (needs to have caseId, targetId, sourceId, noOfPersons, noOfDays)
   * @return bool if invalid params
   * @access public
   * @static
   */
  public static function createDebit($params) {
    if (!isset($params['caseId']) || !isset($params['targetId']) || !isset($params['sourceId']) || !isset($params['noOfPersons']) || !isset($params['noOfDays'])) {
      return FALSE;
    }
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $baseAmount = self::calculateBaseAmount() * $params['noOfDays'] * $params['noOfPersons'];
    $accountableAmount = self::calculateAccountableAmount() * $params['noOfDays'] * $params['noOfPersons'];
    $bdsaAmount = $baseAmount + $accountableAmount;
    $subject = self::buildBusinessDsaActivitySubject($params['noOfPersons'], $params['noOfDays']);
    $activityParams = array(
      'activity_type_id' => $extensionConfig->getDebBdsaActivityTypeId(),
      'target_id' => $params['targetId'],
      'source_id' => $params['sourceId'],
      'subject' => $subject,
      'status_id' => $extensionConfig->getPayableActivityStatusValue(),
      'case_id' => $params['caseId']);
    $createdActivity = civicrm_api3('Activity', 'Create', $activityParams);
    self::createBusinessDsaRecord($createdActivity['id'], $bdsaAmount, $params['noOfDays'], $params['noOfPersons'], 'D');
  }

  /**
   * Method to create record in custom group for business dsa activity
   *
   * @param int $activityId
   * @param int $bdsaAmount
   * @param int $noOfDays
   * @param int $noOfPersons
   * @access protected
   * @static
   */
  protected static function createBusinessDsaRecord($activityId, $bdsaAmount, $noOfDays, $noOfPersons) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();

    $insertClauses[] = 'entity_id = %1';
    $insertClauses[] = $extensionConfig->getBdsaAmountCustomFieldColumn().' = %2';
    $insertClauses[] = $extensionConfig->getBdsaNoOfPersonsCustomFieldColumn().' = %3';
    $insertClauses[] = $extensionConfig->getBdsaNoOfDaysCustomFieldColumn().' = %4';

    $insertParams = array(
      1 => array($activityId, 'Integer'),
      2 => array($bdsaAmount, 'Integer'),
      3 => array($noOfPersons, 'Integer'),
      4 => array($noOfDays, 'Integer'));

    $query = 'INSERT INTO '.$extensionConfig->getBdsaCustomGroupTable().' SET '.implode(', ', $insertClauses);
    CRM_Core_DAO::executeQuery($query, $insertParams);
  }

  /**
   * Method to update the subject for the activity
   *
   * @param int $noOfPersons
   * @param int $noOfDays
   * @return string $subjectString
   * @access protected
   * @static
   */
  protected static function buildBusinessDsaActivitySubject($noOfPersons, $noOfDays) {
    $baseAmount = self::calculateBaseAmount() * $noOfDays * $noOfPersons;
    $accountableAmount = self::calculateAccountableAmount() * $noOfDays * $noOfPersons;
    $subjectString = 'Business DSA (Base amount '.CRM_Utils_Money::format($baseAmount).' and accountable amount '.CRM_Utils_Money::format($accountableAmount).', '.$noOfPersons.' persons and '.$noOfDays.' days)';

    return $subjectString;
  }

  /**
   * Method to set the activity types list for the CaseView summary for Business
   *
   * @param $form
   * @access public
   * @static
   */
  public static function modifyFormActivityTypesList(&$form) {
    $typeIndex = $form->_elementIndex['activity_type_id'];
    self::removeInvalidActivityTypeIdsFromList($form->_elements[$typeIndex]->_options, $form->_caseID);
  }

  /**
   * Method to remove unwanted list options for activity types
   *
   * @param array $typeOptions
   * @param int $caseId
   * @access protected
   * @static
   */
  protected static function removeInvalidActivityTypeIdsFromList(&$typeOptions, $caseId) {
    $typeIdsToBeRemoved = self::getActivityTypeIdsToBeRemoved($caseId);
    foreach ($typeOptions as $typeOptionId => $typeOption) {
      if (in_array($typeOption['attr']['value'], $typeIdsToBeRemoved)) {
        unset($typeOptions[$typeOptionId]);
      }
    }
  }

  /**
   * Method to get the activity type ids to be removed from form list
   *
   * @param int $caseId
   * @return array $typeIdsToBeRemoved
   * @access protected
   * @static
   */
  protected static function getActivityTypeIdsToBeRemoved($caseId) {
    $typeIdsToBeRemoved = array();
    $extensionConfig = CRM_Businessdsa_Config::singleton();

    if (!CRM_Core_Permission::check('edit DSA Activity')) {
      $typeIdsToBeRemoved[] = $extensionConfig->getCredBdsaActivityTypeId();
      $typeIdsToBeRemoved[] = $extensionConfig->getDebBdsaActivityTypeId();
    } else {
      if (!self::dsaCanBeCredited($caseId)) {
        $typeIdsToBeRemoved[] = $extensionConfig->getCredBdsaActivityTypeId();
      }
      if (!self::dsaCanBeDebited($caseId)) {
        $typeIdsToBeRemoved[] = $extensionConfig->getDebBdsaActivityTypeId();
      }
    }
    return $typeIdsToBeRemoved;
  }

  /**
   * Method to determine if the business DSA on the case can be credited
   * This can only be done if there is a debet business dsa activity on the case
   *
   * @param int $caseId
   * @return bool
   * @access protected
   * @static
   */
  protected static function dsaCanBeCredited($caseId) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $debitParams = array(
      'case_id' => $caseId,
      'activity_type_id' => $extensionConfig->getDebBdsaActivityTypeId());
    $activityCount = civicrm_api3('CaseActivity', 'Getcount', $debitParams);
    if ($activityCount > 0) {
      return TRUE;
    }
    return FALSE;
  }
  /**
   * Method to determine if the debet business DSA on the case can be created
   * (only if not exists yet)
   *
   * @param int $caseId
   * @return bool
   * @access protected
   * @static
   */
  protected static function dsaCanBeDebited($caseId) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $debitParams = array(
      'case_id' => $caseId,
      'activity_type_id' => $extensionConfig->getDebBdsaActivityTypeId());
    $activityCount = civicrm_api3('CaseActivity', 'Getcount', $debitParams);
    if ($activityCount == 0) {
      if (CRM_Core_Permission::check('create DSA Activity')) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Method to credit business DSA (delete if status = Payable, credit if status = Paid
   *
   * @param int $caseId
   * @access public
   * @static
   */
  public static function createCredit($caseId) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $debitParams = array(
      'case_id' => $caseId,
      'activity_type_id' => $extensionConfig->getDebBdsaActivityTypeId());
    $debitActivity = civicrm_api3('Activity', 'Getsingle', $debitParams);
    if ($debitActivity['status_id'] == $extensionConfig->getPayableActivityStatusValue()) {
      civicrm_api3('Activity', 'Delete', array('id' => $debitActivity['id']));
    } else {
      $creditParams = array(
        'id' => $debitActivity['id'],
        'activity_type_id' => $extensionConfig->getCredBdsaActivityTypeId(),
        'status_id' => $extensionConfig->getPayableActivityStatusValue());
      civicrm_api3('Activity', 'Create', $creditParams);
    }
  }
}
