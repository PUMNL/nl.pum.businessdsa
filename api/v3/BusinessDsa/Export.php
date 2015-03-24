<?php

/**
 * BusinessDsa.Export API
 * API to export the relevant business DSA's to PUM FA.
 *
 * Logic:
 * - read all existing business dsa's from civicrm_activity (based on date range in params) and related custom group where
 *   status is payable and parent case is not deleted
 * - for every read record: from a string line in the return array
 * - for every read and processed record: set the status of the business dsa to paid
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 24 March 2015
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_business_dsa_export($params) {
  $returnValues = CRM_Businessdsa_BAO_BusinessDsa::getPayableBdsa('', '');
  CRM_Core_Error::debug('data', $returnValues);
  exit();
  return civicrm_api3_create_success($returnValues, $params, 'BusinessDsa', 'Export');
}
