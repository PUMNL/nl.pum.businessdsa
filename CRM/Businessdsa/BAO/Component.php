<?php
/**
 * BAO Component for Business DSA Component
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
class CRM_Businessdsa_BAO_Component extends CRM_Businessdsa_DAO_Component {

  /**
   * Function to get values
   *
   * @return array $result found rows with data
   * @access public
   * @static
   */
  public static function getValues($params) {
    $result = array();
    $component = new CRM_Businessdsa_BAO_Component();
    if (!empty($params)) {
      $fields = self::fields();
      foreach ($params as $key => $value) {
        if (isset($fields[$key])) {
          $component->$key = $value;
        }
      }
    }
    $component->find();
    while ($component->fetch()) {
      $row = array();
      self::storeValues($component, $row);
      $result[$row['id']] = $row;
    }
    return $result;
  }

  /**
   * Function to add or update component
   *
   * @param array $params
   * @return array $result
   * @access public
   * @throws Exception when params is empty
   * @static
   */
  public static function add($params) {
    $result = array();
    if (empty($params)) {
      throw new Exception('Params can not be empty when adding or updating a business dsa component');
    }
    $component = new CRM_Businessdsa_BAO_Component();
    $fields = self::fields();
    foreach ($params as $key => $value) {
      if (isset($fields[$key])) {
        $component->$key = $value;
      }
    }
    $component->save();
    self::storeValues($component, $result);
    return $result;
  }

  /**
   * Function to delete a compnent by id
   *
   * @param int $componentId
   * @throws Exception when componentId is empty
   */
  public static function deleteById($componentId) {
    if (empty($componentId)) {
      throw new Exception('component id can not be empty when attempting to delete a business dsa component');
    }
    $component = new CRM_Businessdsa_BAO_Component();
    $component->id = $componentId;
    $component->delete();
  }

  /**
   * Function to disable a component
   *
   * @param int $componentId
   * @throws Exception when compnentId is empty
   * @access public
   * @static
   */
  public static function disable($componentId) {
    if (empty($componentId)) {
      throw new Exception('component id can not be empty when attempting to disable a business dsa component');
    }
    $component = new CRM_Businessdsa_BAO_Component();
    $component->id = $componentId;
    $component->find(true);
    self::add(array('id' => $component->id, 'is_active' => 0));
  }

  /**
   * Function to enable a component
   *
   * @param int $componentId
   * @throws Exception when componentId is empty
   * @access public
   * @static
   */
  public static function enable($componentId) {
    if (empty($componentId)) {
      throw new Exception('component id can not be empty when attempting to enable a business dsa component');
    }
    $component = new CRM_Businessdsa_BAO_Component();
    $component->id = $componentId;
    $component->find(true);
    self::add(array('id' => $component->id, 'is_active' => 1));
  }

  /**
   * Function to calculate the business DSA
   * - total amount is sum of all amounts of all active components
   * - business DSA is total amount * no of days * no of persons
   *
   * @param int $noOfDays
   * @param int $noOfPersons
   * @return double $dsaAmount
   * @access public
   * @static
   */
  public static function calculateBusinessDsa($noOfDays, $noOfPersons) {
    $baseAmount = self::calculateBaseAmount();
    $dsaAmount = ($baseAmount * $noOfDays) * $noOfPersons;
    return $dsaAmount;
  }

  /**
   * Function to sum all amounts of active components into base amount
   *
   * @return double $baseAmount
   * @access public
   * @static
   */
  public static function calculateBaseAmount() {
    $baseAmount = 0;
    $component = new CRM_Businessdsa_BAO_Component();
    $component->is_active = 1;
    $component->find();
    while ($component->fetch()) {
      $baseAmount = $baseAmount + $component->dsa_amount;
    }
    return $baseAmount;
  }

  /**
   * Function to set the activity status list in a form for business dsa
   *
   * @param object $form
   * @access public
   * @static
   */
  public static function modifyFormActivityStatusList(&$form) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    if ($form->_caseType == $extensionConfig->getBusinessCaseTypeName()) {
      $statusIndex = $form->_elementIndex['status_id'];
      $allowedStatus = $extensionConfig->getBusinessActivityStatus();
      /*
       * remove unwanted statusses
       */
      foreach ($form->_elements[$statusIndex]->_options as $statusOptionId => $statusOption) {
        if (!in_array($statusOption['text'], $allowedStatus) && $statusOptionId != 0) {
          unset($form->_elements[$statusIndex]->_options[$statusOptionId]);
        }
        if ($statusOption['text'] == 'Waiting Approval') {
          $defaults['status_id'] = $statusOption['attr']['value'];
          $form->setDefaults($defaults);
        }
      }
      /*
       * add payable and paid if not present
       */
      self::addBusinessDsaStatusOptions($form->_elements[$statusIndex]->_options);
    }
  }

  /**
   * Function to add the business dsa activity status options if not exists in a form
   *
   * @param array $statusOptions
   * @access protected
   * @static
   *
   */
  protected static function addBusinessDsaStatusOptions(&$statusOptions) {
    $maxId = 0;
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $payableStatusOptionId = 0;
    $paidStatusOptionId = 0;
    foreach ($statusOptions as $statusOptionId => $statusOption) {
      if ($statusOptionId > $maxId) {
        $maxId = $statusOptionId;
      }
      switch ($statusOption['text']) {
        case 'Paid':
          $paidStatusOptionId = $statusOptionId;
          break;
        case 'Payable':
          $payableStatusOptionId = $statusOptionId;
          break;
      }
    }
    if (empty($paidStatusOptionId)) {
      $maxId++;
      $statusOptions[$maxId]['text'] = $extensionConfig->getPaidActivityStatusText();
      $statusOptions[$maxId]['attr']['value'] = $extensionConfig->getPaidActivityStatusValue();
    }
    if (empty($payableStatusOptionId)) {
      $maxId++;
      $statusOptions[$maxId]['text'] = $extensionConfig->getPayableActivityStatusText();
      $statusOptions[$maxId]['attr']['value'] = $extensionConfig->getPayableActivityStatusValue();
    }
  }

  /**
   * Functon to process the business dsa from a form
   *
   * @param array $formValues
   * @param int $activityId
   * @access public
   * @static
   */
  public static function processFormBusinessDsa($formValues, $activityId) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $noOfDaysField = self::getFormCustomField($extensionConfig->getBdsaNoOfDaysCustomFieldId(), $formValues);
    $noOfPersonsField = self::getFormCustomField($extensionConfig->getBdsaNoOfPersonsCustomFieldId(), $formValues);
    if (!empty($noOfDaysField) && !empty($noOfPersonsField)) {
      $dsaAmount = self::calculateBusinessDsa($formValues[$noOfDaysField], $formValues[$noOfPersonsField]);
      $query = 'UPDATE '.$extensionConfig->getBdsaCustomGroupTable().' SET '.$extensionConfig->getBdsaAmountCustomFieldColumn().
        ' = %1 WHERE entity_id = %2';
      $params = array(
        1 => array($dsaAmount, 'Positive'),
        2 => array($activityId, 'Positive'));
      CRM_Core_DAO::executeQuery($query, $params);
    }
  }

  /**
   * Function to get custom field from form values
   *
   * @param int $customFieldId
   * @param array $formValues
   * @return string
   */
  protected static function getFormCustomField($customFieldId, $formValues) {
    foreach ($formValues as $fieldName => $fieldValue) {
      $nameParts = explode('_', $fieldName);
      if ($nameParts[0] == 'custom') {
        if (isset($nameParts[1]) && $nameParts[1] == $customFieldId) {
          return $fieldName;
        }
      }
    }
    return '';
  }
}
