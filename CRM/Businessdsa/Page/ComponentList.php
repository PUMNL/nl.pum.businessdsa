<?php
/**
 * Page ComponentList to list all Business DSA Components
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
require_once 'CRM/Core/Page.php';

class CRM_Businessdsa_Page_ComponentList extends CRM_Core_Page {

  /**
   * Standard run function created when generating page with Civix
   *
   * @access public
   */
  function run() {
    $this->setPageConfiguration();
    $displayComponents = $this->getComponents();
    $this->assign('components', $displayComponents);
    parent::run();
  }

  /**
   * Function to get the data from civicrm_business_dsa_component
   *
   * @return array $displayComponents
   * @access protected
   */
  protected function getComponents() {
    $displayComponents = array();
    $components = CRM_Businessdsa_BAO_Component::getValues(array());
    foreach ($components as $componentId => $component) {
      $displayComponents[$componentId] = $this->setDisplayRow($component);
    }
    return $displayComponents;
  }

  /**
   * Function to compose a row to be displayed on the page
   *
   * @param array $componentRow
   * @return array $displayRow
   * @access protected
   */
  protected function setDisplayRow($componentRow) {
    $displayRow = array();
    if (isset($componentRow['name'])) {
      $displayRow['name'] = $componentRow['name'];
    }
    if (isset($componentRow['dsa_amount'])) {
      $displayRow['amount'] = $componentRow['dsa_amount'];
    }
    if (isset($componentRow['description'])) {
      $displayRow['description'] = substr($componentRow['description'], 0, 30);
      if (strlen($componentRow['description']) > 30) {
        $displayRow['description'] .= '...';
      }
    }
    if (isset($componentRow['is_active'])) {
      $displayRow['enabled'] = CRM_Threepeas_Utils::setIsActive($componentRow['is_active']);
    }
    if (isset($componentRow['modified_date'])) {
      $displayRow['modified_date'] = CRM_Threepeas_Utils::setProjectDate($componentRow['modified_date']);
    }
    if (isset($componentRow['modified_user_id'])) {
      $displayRow['modified_by'] = CRM_Threepeas_Utils::getContactName($componentRow['modified_user_id']);
    }
    if (isset($componentRow['created_date'])) {
      $displayRow['created_date'] = CRM_Threepeas_Utils::setProjectDate($componentRow['created_date']);
    }
    if (isset($componentRow['created_user_id'])) {
      $displayRow['created_by'] = CRM_Threepeas_Utils::getContactName($componentRow['created_user_id']);
    }
    $displayRow['actions'] = $this->setRowActions($componentRow);
    return $displayRow;
  }

  /**
   * Function to set the row action urls and links for each row
   *
   * @param array $componentRow
   * @return array $pageActions
   * @access protected
   */
  protected function setRowActions($componentRow) {
    $pageActions = array();
    $editUrl = CRM_Utils_System::url('civicrm/component', 'action=update&cid='.$componentRow['id'], true);
    $viewUrl = CRM_Utils_System::url('civicrm/component', 'action=view&cid='.$componentRow['id'], true);
    $pageActions[] = '<a class="action-item" title="View" href="'.$viewUrl.'">View</a>';
    $pageActions[] = '<a class="action-item" title="Edit" href="'.$editUrl.'">Edit</a>';
    if ($componentRow['is_active'] == 1) {
      $disableUrl = CRM_Utils_System::url('civicrm/component', 'action=disable&cid='.$componentRow['id'], true);
      $pageActions[] ='<a class="action-item" title="Disable" href="'.$disableUrl.'">Disable</a>';
    } else {
      $enableUrl = CRM_Utils_System::url('civicrm/component', 'action=enable&cid='.$componentRow['id'], true);
      $pageActions[] ='<a class="action-item" title="Enable" href="'.$enableUrl.'">Enable</a>';
    }
    $deleteUrl = CRM_Utils_System::url('civicrm/component', 'action=delete&cid='.$componentRow['id']);
    $pageActions[] = '<a class="action-item" title="Delete" href="'.$deleteUrl.'">Delete</a>';
    return $pageActions;
  }

  /**
   * Function to set the page configuration
   *
   * @access protected
   */
  protected function setPageConfiguration() {
    CRM_Utils_System::setTitle(ts('Business DSA Components'));
    $this->assign('addUrl', CRM_Utils_System::url('civicrm/component','action=add', true));
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/componentlist', 'reset=1', true));
  }
}
