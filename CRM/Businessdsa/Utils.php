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
}