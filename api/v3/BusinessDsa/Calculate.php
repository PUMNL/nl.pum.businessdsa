<?php
/**
 * BusinessDsa.Calculate API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_business_dsa_calculate_spec(&$spec) {
  $spec['no_of_days']['api_required'] = 1;
  $spec['no_of_persons']['api_required'] = 1;
}

/**
 * BusinessDsa.Calculate API
 * API to calculate the business dsa amount
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 25 Feb 2015
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_business_dsa_calculate($params) {
  /*
   * throw Exception if mandatory params no_of_days and no_of_persons not present or empty
   */
  if (!isset($params['no_of_days']) || empty($params['no_of_days'])) {
    throw new API_Exception('no_of_days is a required param and can not be empty');
  }
  if (!isset($params['no_of_persons']) || empty($params['no_of_persons'])) {
    throw new API_Exception('no_of_persons is a required param and can not be empty');
  }
  $dsaAmount = CRM_Businessdsa_BAO_Component::calculateBusinessDsa($params['no_of_days'], $params['no_of_persons']);
  $returnValues['amount'] = $dsaAmount;
  return civicrm_api3_create_success($returnValues, $params, 'BusinessDsa', 'Calculate');
}
