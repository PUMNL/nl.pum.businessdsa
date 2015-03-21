<?php
/**
 * Created by PhpStorm.
 * User: erik
 * Date: 20-2-15
 * Time: 17:05
 */

class CRM_Businessdsa_DAO_Component extends CRM_Core_DAO {
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  static $_fields = null;
  static $_export = null;
  /**
   * empty definition for virtual function
   */
  static function getTableName() {
    return 'civicrm_business_dsa_component';
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true
        ) ,
        'name' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'maxlength' => 75,
        ) ,
        'description' => array(
          'name' => 'description',
          'type' => CRM_Utils_Type::T_TEXT,
        ),
        'dsa_amount' => array(
          'name' => 'dsa_amount',
          'type' => CRM_Utils_Type::T_INT,
        ),
        'accountable_advance' => array(
          'name' => 'accountable_advance',
          'type' => CRM_Utils_Type::T_INT
        ),
        'modified_date' => array(
          'name' => 'modified_date',
          'type' => CRM_Utils_Type::T_DATE,
        ),
        'modified_user_id' => array(
          'name' => 'modified_user_id',
          'type' => CRM_Utils_Type::T_INT,
        ),
        'created_date' => array(
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE,
        ),
        'mcreated_user_id' => array(
          'name' => 'created_user_id',
          'type' => CRM_Utils_Type::T_INT,
        ),
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_INT,
        ),
      );
    }
    return self::$_fields;
  }
  /**
   * Returns an array containing, for each field, the array key used for that
   * field in self::$_fields.
   *
   * @access public
   * @return array
   */
  static function &fieldKeys() {
    if (!(self::$_fieldKeys)) {
      self::$_fieldKeys = array(
        'id' => 'id',
        'name' => 'name',
        'description' => 'description',
        'dsa_amount' => 'dsa_amount',
        'accountable_advance' => 'accountable_advance',
        'modified_date' => 'modified_date',
        'modified_user_id' => 'modified_user_id',
        'created_date' => 'created_date',
        'created_user_id' => 'created_user_id',
        'is_active' => 'is_active'
      );
    }
    return self::$_fieldKeys;
  }
  /**
   * returns the list of fields that can be exported
   *
   * @access public
   * return array
   * @static
   */
  static function &export($prefix = false)
  {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['activity'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}