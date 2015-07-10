<?php

/**
 * Collection of upgrade steps
 */
class CRM_Businessdsa_Upgrader extends CRM_Businessdsa_Upgrader_Base {
  public function install() {
    $this->executeSqlFile('sql/createBusinessDSAComponent.sql');
  }
  
 /**
  * Upgrade 1001 - add general_ledger option value 'business_dsa_accountable'
  * @date 14 May 2014
  */
  public function upgrade_1001() {
    $this->ctx->log->info('Applying update 1001 (add general_ledger option value \'business_dsa_accountable\')');
	$optionGroupName = 'general_ledger';
	$optionValueName = 'business_dsa_accountable';
    // retrieve option group
    $optionGroupId = 0;
    $params = array(
      'name' => $optionGroupName,
      'return' => 'id');
    try {
      $optionGroupId = civicrm_api3('OptionGroup', 'Getvalue', $params);
    } catch (CiviCRM_API3_Exception $ex) {
    }
    if ($optionGroupId == 0) {
      return FALSE;
    }
    // retrieve option value
    $optionValueParams = array(
      'option_group_id' => $optionGroupId,
      'name' => $optionValueName,
      'return' => 'value');
    try {
      $optionValue = civicrm_api3('OptionValue', 'Getvalue', $optionValueParams);
    } catch (CiviCRM_API3_Exception $ex) {
      if ($ex->getMessage() == 'Expected one OptionValue but found 0') {
        // build new option value
        $newParams = array(
          'option_group_id' => $optionGroupId,
          'name' => $optionValueName,
          'label' => 'Business Dsa Accountable',
          'is_active' => 1);
        $newOptionValue = civicrm_api3('OptionValue', 'Create', $newParams);
        // show instruction to set a proper value
        $session = CRM_Core_Session::singleton();
        $session::setStatus('Please make sure to set the correct value for "' . $optionValueName . '" in option group "' . $optionGroupName . '".', 'New option value added', 'info', array('expires'=>0));
      }
    }
    return TRUE;
  }
  
}
