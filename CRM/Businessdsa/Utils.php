<?php
/**
 * Class with general static util functions for business dsa
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @license AGPL-V3.0
 */

class CRM_Businessdsa_Utils {

  /**
   * Internal function to retrieve the activity id in add mode when checking Case_Form_Acitivity
   * At that time activity is already in the database but id is not part of the form object
   *
   * @param int $caseId
   * @return int $activityId
   * @access public
   * @static
   */
  public static function getActivityIdAtFormAdd($caseId) {
    $activityId = 0;
    $query = 'SELECT MAX(activity_id) AS maxActivityId FROM civicrm_case_activity WHERE case_id = %1';
    $params = array(1 => array($caseId, 'Positive'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      $activityId = $dao->maxActivityId;
    }
    return $activityId;
  }

  /**
   * Method to return action param for form processing
   *
   * @param int $action
   * @return string $formAction
   */
  public static function getFormAction($action) {
    switch ($action) {
      case CRM_Core_Action::DELETE:
        $formAction = 'delete';
        break;
      case CRM_Core_Action::UPDATE:
        $formAction = 'update';
        break;
      case CRM_Core_Action::ADD:
        $formAction = 'add';
        break;
      default:
        $formAction = '';
        break;
    }
    return $formAction;
  }

  /**
   * Public function to generate label from name
   *
   * @param $name
   * @return string
   * @access public
   * @static
   */
  public static function buildLabelFromName($name) {
    $nameParts = explode('_', strtolower($name));
    foreach ($nameParts as $key => $value) {
      $nameParts[$key] = ucfirst($value);
    }
    return implode(' ', $nameParts);
  }

  /**
   * Method to get the option group id with name
   *
   * @param string $optionGroupName
   * @return int $optionGroupId
   * @access public
   * @static
   */
  public static function getOptionGroupIdWithName($optionGroupName) {
    $optionGroupId = 0;
    if (!empty($optionGroupName)) {
      $params = array(
        'name' => $optionGroupName,
        'is_active' => 1,
        'return' => 'id');
      try {
        $optionGroupId = civicrm_api3('OptionGroup', 'Getvalue', $params);
      } catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return $optionGroupId;
  }

  /**
   * Method to create option value if not exists and return value if it does
   *
   * @param int $optionGroupId
   * @param string $optionName
   * @return mixed $optionValue
   * @throws Exception when params empty
   * @access public
   * @static
   */
  public static function createOptionValueIfNotExists($optionGroupId, $optionName) {
    if (empty($optionName) || empty($optionGroupId)) {
      throw new Exception('Option label and option group id can not be empty when you try to create or check one');
    }
    $optionValueParams = array(
      'option_group_id' => $optionGroupId,
      'name' => $optionName,
      'is_active' => 1,
      'return' => 'value');
    try {
      $optionValue = civicrm_api3('OptionValue', 'Getvalue', $optionValueParams);
    } catch (CiviCRM_API3_Exception $ex) {
      if ($ex->getMessage() == 'Expected one OptionValue but found 0') {
        $newParams = array(
          'option_group_id' => $optionGroupId,
          'name' => $optionName,
          'label' => self::buildLabelFromName($optionName),
          'is_active' => 1);
        $newOptionValue = civicrm_api3('OptionValue', 'Create', $newParams);
        $optionValue = $newOptionValue['values'][$newOptionValue['id']]['value'];
      }
    }
    return $optionValue;
  }

  /**
   * Method to get the short name for a contact
   *
   * @param int $contactId
   * @return string
   * @access public
   * @static
   */
  public static function getShortnameForContact($contactId) {
    if (empty($contactId)) {
      return '';
    }
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $query = 'SELECT '.$extensionConfig->getShortNameColumn().' AS shortName FROM '
      .$extensionConfig->getAdditionalDataCustomGroupTable.' WHERE entity_id = %1';
    $params = array(1 => array($contactId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      return $dao->shortName;
    } else {
      return '';
    }
  }

  /**
   * Method to get custom group with name (expecting to be unique)
   *
   * @param string $customGroupName
   * @return array
   * @access public
   * @static
   */
  public static function getCustomGroup($customGroupName) {
    if (empty($customGroupName)) {
      return array();
    }
    $customGroupParams = array('name' => $customGroupName);
    try {
      $customGroup = civicrm_api3('CustomGroup', 'Getsingle', $customGroupParams);
      return $customGroup;
    } catch (CiviCRM_API3_Exception $ex) {
      return array();
    }
  }
  public static function getAllCustomFields($customGroupId) {
    if (empty($customGroupId)) {
      return array();
    }
    try {
      $customFields = civicrm_api3('CustomField', 'Get', array('custom_group_id' => $customGroupId));
      return $customFields['values'];
    } catch (CiviCRM_API3_Exception $ex) {
      return array();
    }
  }

  /**
   * Method to get custom field with custom group id and name (expecting to be unique)
   *
   * @param int $customGroupId
   * @param string $customFieldName
   * @return array
   * @access public
   * @static
   */
  public static function getSingleCustomField($customGroupId, $customFieldName) {
    if (empty($customGroupId) || empty($customFieldName)) {
      return array();
    }
    $customFieldParams = array(
      'custom_group_id' => $customGroupId,
      'name' => $customFieldName);
    try {
      $customField = civicrm_api3('CustomField', 'Getsingle', $customFieldParams);
      return $customField;
    } catch (CiviCRM_API3_Exception $ex) {
      return array();
    }
  }

  /**
   * Method to format amount for export (string expecting 2 decimals at the end)
   *
   * @param int $amount
   * @return string
   * @throws Exception when amount not numeric
   * @access public
   * @static
   */
  public static function formatAmountForExport($amount) {
    if (empty($amount) || !is_numeric($amount)) {
      throw new Exception(ts('Amount '.$amount.' is not numeric and can not be formatted for download'));
    }
    $amount = $amount * 100;
    $amountString = (string) $amount;
    return $amountString;
  }
}