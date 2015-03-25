<?php

require_once 'businessdsa.civix.php';

/**
 * Implementation of hook civicrm_navigationMenu
 * to add a Business DSA menu item in the Administer menu
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function businessdsa_civicrm_navigationMenu( &$params ) {
  $item = array (
    'name' => ts('Business DSA Components'),
    'url' => CRM_Utils_System::url('civicrm/componentlist', 'reset=1', true),
    'permission' => 'administer CiviCRM',
  );
  _businessdsa_civix_insert_navigation_menu($params, 'Administer', $item);
}
/**
 * Implementation of hook civicrm_buildForm
 *
 * @param string $formName
 * @param object $form
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function businessdsa_civicrm_buildForm($formName, &$form) {
  /*
   * for manage Case form, determine if business DSA or credit business DSA are allowed operations
   */
  if ($formName == 'CRM_Case_Form_CaseView') {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    if ($form->_caseType == $extensionConfig->getBusinessCaseTypeName()) {
      CRM_Businessdsa_BAO_BusinessDsa::modifyFormActivityTypesList($form);
    }
  }
  /*
   * if business DSA activity added to case, process debit or credit
   */
  if ($formName == 'CRM_Case_Form_Activity') {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $bdsaAction = CRM_Businessdsa_Utils::getFormAction($form->_action);
    if ($form->_caseType == $extensionConfig->getBusinessCaseTypeName()) {
      switch ($form->_activityTypeId) {
        case $extensionConfig->getDebBdsaActivityTypeId():
          $urlParams = 'reset=1&action='.$bdsaAction.'&cid='.$form->_caseId.'&tid='.$form->getVar('_targetContactId').'&sid='.$form->getVar('_sourceContactId');
          $bdsaUrl = CRM_Utils_System::url('civicrm/businessdsa', $urlParams, true);
          CRM_Utils_System::redirect($bdsaUrl);
          break;
        case $extensionConfig->getCredBdsaActivityTypeId():
          CRM_Businessdsa_BAO_BusinessDsa::createCredit($form->_caseId);
          $session = CRM_Core_Session::singleton();
          CRM_Utils_System::redirect($session->readUserContext());
          break;
      }
    }
  }
}

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function businessdsa_civicrm_config(&$config) {
  _businessdsa_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function businessdsa_civicrm_xmlMenu(&$files) {
  _businessdsa_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function businessdsa_civicrm_install() {
  /*
   * check if extensions that are required are active on install
   */
  _businessdsa_requiredExtensions();

  _businessdsa_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function businessdsa_civicrm_uninstall() {
  _businessdsa_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function businessdsa_civicrm_enable() {
  _businessdsa_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function businessdsa_civicrm_disable() {
  _businessdsa_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function businessdsa_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _businessdsa_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function businessdsa_civicrm_managed(&$entities) {
  _businessdsa_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function businessdsa_civicrm_caseTypes(&$caseTypes) {
  _businessdsa_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function businessdsa_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _businessdsa_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Function to check if the required extensions are installed:
 * - org.civicoop.api.caseactivity
 * - nl.pum.dsa
 * - nl.pum.sequence
 * - nl.pum.threepeas
 *
 * @throws Exception if one of the required extensions is not active
 */
function _businessdsa_requiredExtensions() {
  $localExtensions = civicrm_api3('Extension', 'Get', array());
  $requiredExtensions = array('nl.pum.dsa', 'nl.pum.sequence', 'nl.pum.threepeas', 'org.civicoop.api.caseactivity');
  foreach ($requiredExtensions as $requiredKey) {
    if (!in_array($requiredKey, $localExtensions['values'], true)) {
      throw new Exception('Required extension '.$requiredKey.' is not installed');
    } else {
      foreach ($localExtensions as $localExtension) {
        if ($localExtension['key'] == $requiredKey && $localExtension['status'] != 'installed') {
          throw new Exception('Required extension '.$requiredKey.' is not installed');
        }
      }
    }
  }
}

