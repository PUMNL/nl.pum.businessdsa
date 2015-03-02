<?php
/**
 * Class following Singleton pattern for specific extension configuration
 * for Business DSA PUM
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 23 February 2015
 * @license AGPL-3.0

 */
class CRM_Businessdsa_Config {
  /*
   * singleton pattern
   */
  static private $_singleton = NULL;
  /*
   * properties for case type of business
   */
  protected $businessCaseTypeId = NULL;
  protected $businessCaseTypeName = NULL;
  /*
   * properties for business debit/credit dsa activity status
   */
  protected $businessActivityStatus = array();
  protected $paidActivityStatusText = NULL;
  protected $paidActivityStatusValue = NULL;
  protected $payableActivityStatusText = NULL;
  protected $payableActivityStatusValue = NULL;
  /*
   * properties for business dsa activity type en credit business dsa activity type
   */
  protected $debBdsaActTypeId = NULL;
  protected $credBdsaActTypeId = NULL;
  protected $debBdsaActTypeName = NULL;
  protected $credBdsaActTypeName = NULL;
  /*
   * properties for custom group business DSA
   * with fields number of days, number of persons, amount and type (Deb or Cred)
   */
  protected $bdsaCustomGroupName = NULL;
  protected $bdsaCustomGroupId = NULL;
  protected $bdsaCustomGroupTable = NULL;

  protected $bdsaNoOfDaysCustomFieldName = NULL;
  protected $bdsaNoOfDaysCustomFieldId = NULL;
  protected $bdsaNoOfDaysCustomFieldColumn = NULL;

  protected $bdsaNoOfPersonsCustomFieldName = NULL;
  protected $bdsaNoOfPersonsCustomFieldId = NULL;
  protected $bdsaNoOfPersonsCustomFieldColumn = NULL;

  protected $bdsaAmountCustomFieldName = NULL;
  protected $bdsaAmountCustomFieldId = NULL;
  protected $bdsaAmountCustomFieldColumn = NULL;

  protected $bdsaTypeCustomFieldName = NULL;
  protected $bdsaTypeCustomFieldId = NULL;
  protected $bdsaTypeCustomFieldColumn = NULL;

  /**
   * Function to return singleton object
   *
   * @return object $_singleton
   * @access public
   * @static
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Businessdsa_Config();
    }
    return self::$_singleton;
  }
  /**
   * Constructor function
   */
  function __construct() {
    $this->setBusinessCaseType();
    $this->createBusinessDsaActivityTypes();
    $this->setBusinessDsaCustomGroup();
    $this->businessActivityStatus = array('Scheduled', 'Cancelled', 'Paid', 'Payable', 'Waiting Approval');
    $this->setActivityStatus('Paid');
    $this->setActivityStatus('Payable');
  }

  /**
   * Function to return payable activity status value
   *
   * @return int
   * @access public
   */
  public function getPayableActivityStatusValue() {
    return $this->payableActivityStatusValue;
  }

  /**
   * Function to return payable activity status text
   *
   * @return string
   * @access public
   */
  public function getPayableActivityStatusText() {
    return $this->payableActivityStatusText;
  }

  /**
   * Function to return paid activity status value
   *
   * @return int
   * @access public
   */
  public function getPaidActivityStatusValue() {
    return $this->paidActivityStatusValue;
  }

  /**
   * Function to return paid activity status text
   *
   * @return string
   * @access public
   */
  public function getPaidActivityStatusText() {
    return $this->paidActivityStatusText;
  }

  /**
   * Function to return business activity status array with allowed statusses
   *
   * @return array
   * @access public
   */
  public function getBusinessActivityStatus() {
    return $this->businessActivityStatus;
  }
  /**
   * Function to get the business case type name
   *
   * @return string
   * @access public
   */
  public function getBusinessCaseTypeName() {
    return $this->businessCaseTypeName;
  }

  /**
   * Function to get business case type id
   *
   * @return int
   * @acess public
   */
  public function getBusinessCaseTypeId() {
    return $this->businessCaseTypeId;
  }

  /**
   * Function to get business dsa custom field type id
   *
   * @return int
   * @access public
   */
  public function getBdsaTypeCustomFieldId() {
    return $this->bdsaTypeCustomFieldId;
  }

  /**
   * Function to get business dsa custom field type column name
   *
   * @return string
   * @access public
   */
  public function getBdsaTypeCustomFieldColumn() {
    return $this->bdsaTypeCustomFieldColumn;
  }

  /**
   * Function to get business dsa custom field type name
   *
   * @return string
   * @access public
   */
  public function getBdsaTypeCustomFieldName() {
    return $this->bdsaTypeCustomFieldName;
  }

  /**
   * Function to get business dsa custom field amount id
   *
   * @return int
   * @access public
   */
  public function getBdsaAmountCustomFieldId() {
    return $this->bdsaAmountCustomFieldId;
  }

  /**
   * Function to get business dsa custom field amount column name
   *
   * @return string
   * @access public
   */
  public function getBdsaAmountCustomFieldColumn() {
    return $this->bdsaAmountCustomFieldColumn;
  }

  /**
   * Function to get business dsa custom field amount name
   *
   * @return string
   * @access public
   */
  public function getBdsaAmountCustomFieldName() {
    return $this->bdsaAmountCustomFieldName;
  }

  /**
   * Function to get business dsa custom field number of persons id
   *
   * @return int
   * @access public
   */
  public function getBdsaNoOfPersonsCustomFieldId() {
    return $this->bdsaNoOfPersonsCustomFieldId;
  }

  /**
   * Function to get business dsa custom field number of persons column name
   *
   * @return string
   * @access public
   */
  public function getBdsaNoOfPersonsCustomFieldColumn() {
    return $this->bdsaNoOfPersonsCustomFieldColumn;
  }

  /**
   * Function to get business dsa custom field number of persons name
   *
   * @return string
   * @access public
   */
  public function getBdsaNoOfPersonsCustomFieldName() {
    return $this->bdsaNoOfDaysCustomFieldName;
  }

  /**
   * Function to get business dsa custom field number of days id
   *
   * @return int
   * @access public
   */
  public function getBdsaNoOfDaysCustomFieldId() {
    return $this->bdsaNoOfDaysCustomFieldId;
  }

  /**
   * Function to get business dsa custom field number of days column name
   *
   * @return string
   * @access public
   */
  public function getBdsaNoOfDaysCustomFieldColumn() {
    return $this->bdsaNoOfDaysCustomFieldColumn;
  }

  /**
   * Function to get business dsa custom field number of days name
   *
   * @return string
   * @access public
   */
  public function getBdsaNoOfDaysCustomFieldName() {
    return $this->bdsaNoOfDaysCustomFieldName;
  }

  /**
   * Function to get business dsa custom group id
   *
   * @return int
   * @access public
   */
  public function getBdsaCustomGroupId() {
    return $this->bdsaCustomGroupId;
  }

  /**
   * Function to get business dsa custom group table
   *
   * @return string
   * @access public
   */
  public function getBdsaCustomGroupTable() {
    return $this->bdsaCustomGroupTable;
  }

  /**
   * Function to get business dsa custom group name
   *
   * @return string
   * @access public
   */
  public function getBdsaCustomGroupName() {
    return $this->bdsaCustomGroupName;
  }

  /**
   * Function to get credit business dsa activity type name
   *
   * @return string
   * @access public
   */
  public function getCredBdsaActivityTypeName() {
    return $this->credBdsaActTypeName;
  }

  /**
   * Function to get credit business dsa activity type id
   *
   * @return int
   * @access public
   */
  public function getCredBdsaActivityTypeId() {
    return $this->credBdsaActTypeId;
  }

  /**
   * Function to get debit business dsa activity type name
   *
   * @return string
   * @access public
   */
  public function getDebBdsaActivityTypeName() {
    return $this->debBdsaActTypeName;
  }

  /**
   * Function to get debit business dsa activity type id
   *
   * @return int
   * @access public
   */
  public function getDebBdsaActivityTypeId() {
    return $this->debBdsaActTypeId;
  }

  /**
   * Function to create the activity types for debit and credit business dsa if not exist
   *
   * @access protected
   */
  protected function createBusinessDsaActivityTypes() {
    $this->debBdsaActTypeName = 'debet_business_dsa';
    $debetActivityType = CRM_Threepeas_Utils::getActivityTypeWithName($this->debBdsaActTypeName);
    if (empty($debetActivityType)) {
      $debetActivityType = CRM_Threepeas_Utils::createActivityType($this->debBdsaActTypeName, 'Business DSA', 7);
    }
    $this->debBdsaActTypeId = $debetActivityType['value'];

    $this->credBdsaActTypeName = 'credit_business_dsa';
    $creditActivityType = CRM_Threepeas_Utils::getActivityTypeWithName($this->credBdsaActTypeName);
    if (empty($creditActivityType)) {
      $creditActivityType = CRM_Threepeas_Utils::createActivityType($this->credBdsaActTypeName, 'Credit Business DSA', 7);
    }
    $this->credBdsaActTypeId = $creditActivityType['value'];
  }

  /**
   * Function to create custom group and custom fields for business dsa
   *
   * @access protected
   */
  protected function setBusinessDsaCustomGroup()
  {
    $this->bdsaCustomGroupName = 'pum_business_dsa';
    $customGroup = CRM_Threepeas_Utils::getCustomGroup($this->bdsaCustomGroupName);
    if (empty($customGroup)) {
      $this->bdsaCustomGroupTable = 'civicrm_value_pum_business_dsa';
      $this->bdsaCustomGroupId = CRM_Threepeas_Utils::createCustomGroup($this->bdsaCustomGroupName,
        $this->bdsaCustomGroupTable, 'Activity', array($this->debBdsaActTypeId), 'Business DSA');
    } else {
      $this->bdsaCustomGroupId = $customGroup['id'];
      $this->bdsaCustomGroupTable = $customGroup['table_name'];
    }
    $this->setBusinessDsaCustomFields();
  }

  /**
   * Function to create custom fields for business dsa
   *
   * @access protected
   */
  protected function setBusinessDsaCustomFields() {

    $this->bdsaNoOfDaysCustomFieldName = 'bdsa_no_of_days';
    $this->bdsaNoOfPersonsCustomFieldName = 'bdsa_no_of_persons';
    $this->bdsaAmountCustomFieldName = 'bdsa_amount';
    $this->bdsaTypeCustomFieldName = 'bdsa_type';
    $customFieldToBeCreated = array(
      0 => array(
        'name' => 'NoOfDays',
        'label' => 'Number of Days',
        'dataType' => 'Int',
        'htmlType' => 'Text',
        'is_view' => 0,
        'defaultValue' => 0),
      1 => array(
        'name' => 'NoOfPersons',
        'label' => 'Number of Persons',
        'dataType' => 'Int',
        'htmlType' => 'Text',
        'is_view' => 0,
        'defaultValue' => 0),
      2 => array(
        'name' => 'Amount',
        'label' => 'Amount',
        'dataType' => 'Int',
        'htmlType' => 'Text',
        'is_view' => 1,
        'defaultValue' => 0),
      3 => array(
        'name' => 'Type',
        'label' => 'Type',
        'dataType' => 'String',
        'htmlType' => 'Text',
        'is_view' => 1,
        'defaultValue' => 'D'));

    foreach ($customFieldToBeCreated as $customFieldToBeCreated) {
      $nameProperty = 'bdsa'.$customFieldToBeCreated['name'].'CustomFieldName';
      $columnProperty = 'bdsa'.$customFieldToBeCreated['name'].'CustomFieldColumn';
      $idProperty = 'bdsa'.$customFieldToBeCreated['name'].'CustomFieldId';
      $customField = CRM_Threepeas_Utils::getCustomField($this->bdsaCustomGroupId, $this->$nameProperty);
      if (empty($customField)) {
        $this->$columnProperty = $this->$nameProperty;
        $this->$idProperty = CRM_Threepeas_Utils::createCustomField($this->bdsaCustomGroupId,
          $this->$nameProperty,$this->$columnProperty, $customFieldToBeCreated['dataType'], $customFieldToBeCreated['htmlType'],
          $customFieldToBeCreated['defaultValue'], $customFieldToBeCreated['is_view'],  $customFieldToBeCreated['label']);
      } else {
        $this->$idProperty = $customField['id'];
        $this->$columnProperty = $customField['column_name'];
      }
    }
  }

  /**
   * Function to set the case type id for business
   *
   * @throws Exception when API OptionValue Getvalue throws error
   */
  protected function setBusinessCaseType() {
    $optionGroupId = $this->getCaseTypeOptionGroupId();
    $this->businessCaseTypeName = 'Business';
    $params = array(
      'option_group_id' => $optionGroupId,
      'name' => $this->businessCaseTypeName,
      'return' => 'value');
    try {
      $this->businessCaseTypeId = civicrm_api3('OptionValue', 'Getvalue', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find case type Business, error from API OptionValue Getvalue: '
        .$ex->getMessage());
    }
  }

  /**
   * Function to get the case type option group id
   *
   * @return int $caseTypeOptionGroupId
   * @throws Exception when API OptionGroup Getvalue throws error
   * @access protected
   */
  protected function getCaseTypeOptionGroupId() {
    $params = array(
      'name' => 'case_type',
      'return' => 'id');
    try {
      $caseTypeOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find option group with name case_type,
        error from API OptionGroup Getvalue: ' . $ex->getMessage());
    }
    return $caseTypeOptionGroupId;
  }

  /**
   * Function to get or create activity status for business dsa
   *
   * @param string $activityStatusName
   * @access protected
   */
  protected function setActivityStatus($activityStatusName) {
    $textPropertyName = strtolower($activityStatusName).'ActivityStatusText';
    $valuePropertyName = strtolower($activityStatusName).'ActivityStatusValue';
    $optionName = 'dsa_'.strtolower($activityStatusName);
    $activityStatus = CRM_Threepeas_Utils::getActivityStatusWithName($optionName);
    if (empty($activityStatus)) {
      $activityStatus = CRM_Threepeas_Utils::createActivityStatus($optionName, $activityStatusName);
      $this->$textPropertyName = $activityStatusName;
      $this->$valuePropertyName = $activityStatus['value'];
    } else {
      $this->$textPropertyName = $activityStatusName;
      $this->$valuePropertyName = $activityStatus['value'];
    }
  }
}
