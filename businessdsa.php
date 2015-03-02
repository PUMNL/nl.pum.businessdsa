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
    'name'          =>  ts('Business DSA'),
    'url'           =>  CRM_Utils_System::url('civicrm/componentlist', 'reset=1', true),
    'permission'    => 'administer CiviCRM',
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
  if ($formName == 'CRM_Case_Form_Activity') {
    CRM_Businessdsa_BAO_Component::modifyFormActivityStatusList($form);
  }
}

/**
 * Implementation of hook civicrm_postProcess
 *
 * @param string $formName
 * @param object $form
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postProcess
 */
function businessdsa_civicrm_postProcess($formName, $form) {
  if ($formName == 'CRM_Case_Form_Activity') {
    if ($form->_action == CRM_Core_Action::ADD) {
      /*
       * lelijke hack om activity_id op te halen omdat die niet in de form staat in add mode
       */
      $query = 'SELECT MAX(activity_id) AS maxActivityId FROM civicrm_case_activity WHERE case_id = %1';
      $params = array(1 => array($form->_caseId, 'Positive'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      if ($dao->fetch()) {
        $activityId = $dao->maxActivityId;
      }
    } else {
      $activityId = $form->_activityId;
    }
    CRM_Businessdsa_BAO_Component::processFormBusinessDsa($form->_submitValues, $activityId);
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
