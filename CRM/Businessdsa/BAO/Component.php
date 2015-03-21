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
}
