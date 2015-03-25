<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @license AGPL-3.0
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Businessdsa_Form_Component extends CRM_Core_Form {

  protected $componentId = NULL;

  /**
   * Overridden parent method to buildQuickForm (call parent method too)
   *
   * @access public
   */
  function buildQuickForm() {
    $this->addFormElements();
    $this->assign('elementNames', $this->getRenderableElementNames());

    parent::buildQuickForm();
  }

  /**
   * Overridden parent method to add validation rules
   *
   * @access public
   */
  function addRules() {
    switch ($this->_action) {
      case CRM_Core_Action::ADD:
        $this->addFormRule(array('CRM_Businessdsa_Form_Component', 'validateNameAdd'));
        break;
      case CRM_Core_Action::UPDATE:
        $this->addFormRule(array('CRM_Businessdsa_Form_Component', 'validateNameUpdate'));
        break;
    }
  }

  /**
   * Overridden parent method to initiate form
   *
   * @access public
   */
  function preProcess() {
    $this->componentId = CRM_Utils_Request::retrieve('cid', 'Integer');
    if ($this->_action == CRM_Core_Action::ADD) {
      $actionLabel = 'Add';
    } else {
      $actionLabel = 'Edit';
    }
    $this->assign('formHeader', ts($actionLabel.' Business DSA Component'));
    /*
     * if action = delete, disable or enable, execute delete immediately
     */
    switch ($this->_action) {
      case CRM_Core_Action::DELETE:
        CRM_Businessdsa_BAO_Component::deleteById(CRM_Utils_Request::retrieve('cid', 'Positive'));
        $session = CRM_Core_Session::singleton();
        $session->setStatus('Business DSA Component deleted', 'Component deleted', 'success');
        CRM_Utils_System::redirect($session->readUserContext());
        break;
      case CRM_Core_Action::DISABLE:
        CRM_Businessdsa_BAO_Component::disable(CRM_Utils_Request::retrieve('cid', 'Positive'));
        $session = CRM_Core_Session::singleton();
        $session->setStatus('Business DSA Component disabled', 'Component disabled', 'success');
        CRM_Utils_System::redirect($session->readUserContext());
        break;
      case CRM_Core_Action::ENABLE:
        CRM_Businessdsa_BAO_Component::enable(CRM_Utils_Request::retrieve('cid', 'Positive'));
        $session = CRM_Core_Session::singleton();
        $session->setStatus('Business DSA Component enabled', 'Component enabled', 'success');
        CRM_Utils_System::redirect($session->readUserContext());
        break;
    }
  }

  /**
   * Overridden parent method to process form (calls parent method too)
   *
   * @access public
   */
  function postProcess() {
    $this->componentId = $this->_submitValues['component_id'];
    if ($this->_action != CRM_Core_Action::VIEW) {
      $this->saveComponent($this->_submitValues);
    }
    parent::postProcess();
  }

  /**
   * Overridden parent method to set default values
   *
   * @return array $defaults
   * @access public
   */
  function setDefaultValues() {
    $defaults = array();
    switch ($this->_action) {
      case CRM_Core_Action::UPDATE:
        $defaults = $this->setComponentDefaults();
        break;
      case CRM_Core_Action::VIEW:
        $defaults = $this->setComponentDefaults();
        break;
      case CRM_Core_Action::ADD:
        $defaults['is_active'] = 1;
        break;
    }
    $defaults['component_id'] = $this->componentId;
    return $defaults;
  }
  protected function setComponentDefaults() {
    $defaults = array();
    if (!empty($this->componentId)) {
      $component = CRM_Businessdsa_BAO_Component::getValues(array('id' => $this->componentId));
      foreach ($component[$this->componentId] as $componentName => $componentValue) {
        $defaults[$componentName] = $componentValue;
      }
      return $defaults;
    }
  }

  /**
   * Function to add form elements
   *
   * @access protected
   */
  protected function addFormElements() {
    $this->add('hidden', 'component_id', ts('ComponentID'), array('id' => 'component_id'));
    $this->add('text', 'name', ts('Name'), array('size' => CRM_Utils_Type::HUGE), true);
    $this->add('textarea', 'description', ts('Description'), array(
      'rows'  => 4,
      'cols'  => 80), false);
    $this->add('text', 'dsa_amount', ts('Amount'));
    $this->add('checkbox', 'is_active', ts('Enabled'));
    $this->add('checkbox', 'accountable_advance', ts('Accountable'));
    switch ($this->_action) {
      case CRM_Core_Action::VIEW:
        $this->addButtons(array(
          array('type' => 'cancel', 'name' => ts('Done'), 'isDefault' => true)));
        break;
      default:
        $this->addButtons(array(
          array('type' => 'next', 'name' => ts('Save'), 'isDefault' => true,),
          array('type' => 'cancel', 'name' => ts('Cancel'))));
        break;
    }
  }

  /**
   * Function to validate the name of the component does not exist for add
   *
   * @param array $fields
   * @return array $errors or TRUE
   * @access public
   * @static
   */
  static function validateNameAdd($fields) {
    $component = new CRM_Businessdsa_BAO_Component();
    $component->name = $fields['name'];
    if ($component->count() > 0) {
      $errors['name'] = ts('There is already a business DSA component with this name');
      return $errors;
    }
    return TRUE;
  }

  /**
   * Function to validate the name entered does not exist for update
   *
   * @param array $fields
   * @return array $errors or TRUE
   * @access public
   * @static
   */
  static function validateNameUpdate($fields) {
    $component = new CRM_Businessdsa_BAO_Component();
    $component->name = $fields['name'];
    if ($component->count() > 1) {
      $component->fetch();
      if ($component->id != $fields['component_id']) {
        $errors['name'] = ts('There is already a business DSA component with this name');
        return $errors;
      }
    }
    return TRUE;
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   * @access protected
   */
  protected function getRenderableElementNames() {
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Function to save the component values
   *
   * @param $formValues
   * @access protected
   */
  protected function saveComponent($formValues) {
    $params = array();
    $params['is_active'] = 0;
    $params['accountable_advance'] = 0;
    $componentFields = CRM_Businessdsa_BAO_Component::fields();
    foreach($formValues as $key => $value) {
      if (CRM_Utils_Array::value($key, $componentFields)) {
        $params[$key] = $value;
      }
    }
    $session = CRM_Core_Session::singleton();

    switch ($this->_action) {
      case CRM_Core_Action::ADD:
        $params['created_date'] = date('Ymd');
        $params['created_user_id'] = $session->get('userID');
        break;
      case CRM_Core_Action::UPDATE:
        $params['modified_date'] = date('Ymd');
        $params['modified_user_id'] = $session->get('userID');
        $params['id'] = $formValues['component_id'];
        break;
    }
    CRM_Businessdsa_BAO_Component::add($params);
    $session->setStatus(ts('Business DSA Component saved'), ts('Component saved'), 'success');
  }
}
