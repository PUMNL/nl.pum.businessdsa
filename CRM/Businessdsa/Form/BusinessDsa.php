<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Businessdsa_Form_BusinessDsa extends CRM_Core_Form {

  protected $caseId = NULL;
  protected $targetId = NULL;
  protected $sourceId = NULL;
  /**
   * Overridden parent method to build the form
   *
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle('Add Business DSA');
    $this->assign('formHeader', ts('Business DSA data for case ').$this->caseId);
    $this->assign('infoAmount', CRM_Businessdsa_BAO_BusinessDsa::calculateBaseAmount());
    $this->add('hidden', 'caseId', $this->caseId);
    $this->add('hidden', 'targetId', $this->targetId);
    $this->add('hidden', 'sourceId', $this->sourceId);
    $this->add('text', 'noOfPersons', ts('Number of Persons'), array(), true);
    $this->add('text', 'noOfDays', ts('Number of Days'), array(), true);
    $this->addButtons(array(
      array('type' => 'next', 'name' => ts('Save'), 'isDefault' => true,),
      array('type' => 'cancel', 'name' => ts('Cancel'))));

    parent::buildQuickForm();
  }

  /**
   * Overridden parent method before form is built
   *
   * @access public
   */
  public function preProcess() {
    if ($this->_action == CRM_Core_Action::DELETE) {
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts('You can not delete a business DSA activity'), 'Invalid Action', 'error');
      CRM_Utils_System::redirect($session->readUserContext());
    }
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts('You can not edit a business DSA activity, you can credit one and then add a new one'), 'Invalid Action', 'error');
      CRM_Utils_System::redirect($session->readUserContext());
    }

    $this->caseId = CRM_Utils_Request::retrieve('cid', 'Integer');
    $this->targetId = CRM_Utils_Request::retrieve('tid', 'Integer');
    $this->sourceId = CRM_Utils_Request::retrieve('sid', 'Integer');
  }

  /**
   * Overridden parent method to process form values after submit
   *
   * @access public
   */
  public function postProcess() {
    $params = array(
      'caseId' => $this->_submitValues['caseId'],
      'targetId' => $this->_submitValues['targetId'],
      'sourceId' => $this->_submitValues['sourceId'],
      'noOfDays' => $this->_submitValues['noOfDays'],
      'noOfPersons' => $this->_submitValues['noOfPersons']);
    CRM_Businessdsa_BAO_BusinessDsa::createDebit($params);
    parent::postProcess();
  }

  /**
   * Overridden parent method to set validation rules
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Businessdsa_Form_BusinessDsa', 'validateIntegers'));
  }

  /**
   * Function to validate that number of persons and days are integers
   *
   * @param array $fields
   * @return array $errors or TRUE
   * @access public
   * @static
   */
  static function validateIntegers($fields) {
    $errors = array();
    if (!ctype_digit($fields['noOfPersons'])) {
      $errors['noOfPersons'] = ts('Number of persons has to contain a number without decimals');
    }
    if (!ctype_digit($fields['noOfDays'])) {
      $errors['noOfDays'] = ts('Number of days has to contain a number without decimals');
      return $errors;
    }
    if (!empty($errors)) {
      return $errors;
    } else {
      return TRUE;
    }
  }
}
