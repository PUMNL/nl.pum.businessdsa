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
      .$extensionConfig->getAdditionalDataCustomGroupTable().' WHERE entity_id = %1';
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

  /**
   * Method to check if the date is a valid date
   *
   * @param string $date
   * @return bool
   * @access public
   */
  public static function isValidDate($date) {
    $stamp = strtotime($date);
    if (!is_numeric($stamp)) {
      return FALSE;
    }
    $month = date( 'm', $stamp );
    $day   = date( 'd', $stamp );
    $year  = date( 'Y', $stamp );
    if (checkdate($month, $day, $year)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to get the donor code of a donor (contact with subtype donor)
   *
   * @param int $donorId
   * @return string
   * @access public
   */
  public static function getDonorCode($donorId) {
    if (empty($donorId)) {
      return '';
    }
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $query = 'SELECT '.$extensionConfig->getDonorCodeColumn().' AS donorCode FROM '
      .$extensionConfig->getDonorDataCustomGroupTable().' WHERE entity_id = %1';
    $params = array(1 => array($donorId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      return $dao->donorCode;
    }
    return '';
  }

  /**
   * Method to get the pum case data custom data for a case
   *
   * @param int $caseId
   * @return Object
   * @access public
   * @static
   */
  public static function getPumCaseData($caseId) {
    $result = array();
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $pumCaseFields = $extensionConfig->getPumCaseCustomFields();
    $selectFields = array();
    foreach ($pumCaseFields as $pumCaseFieldName => $pumCaseField) {
      $selectFields[] = $pumCaseField['column_name'];
    }
    $query = 'SELECT '.implode(', ', $selectFields).' FROM '
      .$extensionConfig->getPumCaseCustomGroupTable().' WHERE entity_id = %1';
    $params = array(1 => array($caseId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      foreach ($pumCaseFields as $pumCaseFieldName => $pumCaseField) {
        $result[$pumCaseFieldName] = $dao->$pumCaseField['column_name'];
      }
      return $result;
    } else {
      return array();
    }
  }

  /**
   * Method to get the bank information for an expert
   *
   * @param int $expertId (contact_id)
   * @return Object
   * @access public
   * @static
   */
  public static function getExpertBankData($expertId) {
    $result = array();
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $bankFields = $extensionConfig->getBankCustomFields();
    $selectFields = array();
    foreach ($bankFields as $bankFieldName => $bankField) {
      $selectFields[] = $bankField['column_name'];
    }
    $query = 'SELECT '.implode(', ', $selectFields).' FROM '
      .$extensionConfig->getBankCustomGroupTable().' WHERE entity_id = %1';
    $params = array(1 => array($expertId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      foreach ($bankFields as $bankFieldName => $bankField) {
        if ($bankFieldName == 'Bank_Country_ISO_Code') {
          $result[$bankFieldName] = self::getCountryIsoCode($dao->$bankField['column_name']);
        } elseif ($bankFieldName == 'Accountholder_country') {
          $result[$bankFieldName] = self::getCountryIsoCode($dao->$bankField['column_name']);
        } else {
          $result[$bankFieldName] = $dao->$bankField['column_name'];
        }
      }
      return $result;
    } else {
      return array();
    }
  }

  /**
   * Method to get the iso code of a country id
   *
   * @param int $countryId
   * @return string
   * @access public
   * @static
   */
  public static function getCountryIsoCode($countryId) {
    if (empty($countryId)) {
      return '';
    }
    $countryParams = array(
      'id' => $countryId,
      'return' => 'iso_code');
    try {
      $countryIso = civicrm_api3('Country', 'Getvalue', $countryParams);
      return $countryIso;
    } catch (CiviCRM_API3_Exception $ex) {
      return '';
    }
  }

  /**
   * Method to check if the activity type id is a customer contribution
   *
   * @param int $activityTypeId
   * @return bool
   * @access pubic
   * @static
   */
  public static function isCustomerContribution($activityTypeId) {
    $customerContributionTypeId = CRM_Threepeas_Utils::getActivityTypeWithName('Condition: Customer Contribution.');
    if ($activityTypeId == $customerContributionTypeId) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Method to get the primary country of the expert
   *
   * @param int $expertId
   * @return int $countryId
   * @access public
   * @static
   */
  public static function getExpertCountry($expertId) {
    $countryId = null;
    if (!empty($expertId)) {
      $params = array(
        'contact_id' => $expertId,
        'is_primary' => 1,
        'return' => 'country_id'
      );
      try {
        $countryId = civicrm_api3('Address', 'Getvalue', $params);
      } catch (CiviCRM_API3_Exception $ex) {
        $countryId = null;
      }
    }
    return $countryId;
  }

  /**
   * Method to get the main sector of the expert
   *
   * @param int $expertId
   * @return string $mainSector
   * @access public
   * @static
   */
  public static function getMainSector($expertId) {
    $mainSector = null;
    if (!empty($expertId)) {
      $params_mainsectors = array(
        'version' => 3,
        'sequential' => 1,
        'contact_id' => $expertId,
        'is_active' => 1,
        'is_main' => 1
      );
      try {
        $api_data = null;
        $mainSectors = civicrm_api3('ContactSegment', 'get', $params_mainsectors);
        foreach($mainSectors['values'] as $key => $value){
          $api_data[] = $value;
        }
      } catch (CiviCRM_API3_Exception $ex) {
        $mainSectors = null;
        $api_data = null;
      }

      if(is_array($api_data)){
        foreach($api_data as $key => $value){
          if(!empty($value['segment_id'])){
            try {
              $params_segment = array(
                'version' => 3,
                'sequential' => 1,
                'id' => $value['segment_id'],
              );
              $mainSectorData = civicrm_api('Segment', 'getsingle', $params_segment);
              if(!empty($mainSectorData['label'])){
                $mainSector = $mainSectorData['label'];
              }
            } catch (CiviCRM_API3_Exception $ex) {
              $mainSectorData = null;
              $mainSector = null;
            }
          }
        }
      }
    }
    return $mainSector;
  }

  /**
   * Method to get the client of a case
   *
   * @param int $caseId
   * @return int $clientId
   * @access public
   * @static
   */
  public static function getClientOfCase($caseId) {
    $clientId = null;

    try{
      $clientId = CRM_Core_DAO::singleValueQuery('SELECT contact_id FROM civicrm_case_contact WHERE case_id = %1', array(1 => array($caseId, 'Integer')));
    } catch(Exception $e) {
      $clientId = null;
    }
    return $clientId;
  }

  /**
   * Method to get the country of the client
   *
   * @param int $clientId
   * @return string $countryName
   * @access public
   * @static
   */
  public static function getCountryOfClient($clientId) {
    $countryName = null;
    if (!empty($clientId)) {

      try {
        $params_contact_client = array(
          'version' => 3,
          'sequential' => 1,
          'id' => $clientId,
        );
        $contact_client = civicrm_api('Contact', 'getsingle', $params_contact_client);

        if(!empty($contact_client['country'])){
          $countryName = $contact_client['country'];
        }
      } catch (CiviCRM_API3_Exception $ex) {
        $countryName = null;
      }
    }
    return $countryName;
  }
}