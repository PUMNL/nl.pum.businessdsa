<?php
/**
 * BAO for Business DSA
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
class CRM_Businessdsa_BAO_BusinessDsa {

  /**
   * Method to sum all non-accountable amounts of active components into base amount
   *
   * @return double $baseAmount
   * @access public
   * @static
   */
  public static function calculateBaseAmount() {
    $baseAmount = 0;
    $component = new CRM_Businessdsa_BAO_Component();
    $component->is_active = 1;
    $component->accountable_advance = 0;
    $component->find();
    while ($component->fetch()) {
      $baseAmount = $baseAmount + $component->dsa_amount;
    }
    return $baseAmount;
  }

  /**
   * Method to sum all accountable amounts of active components into accountable amount
   * @return int
   */
  public static function calculateAccountableAmount() {
    $accountableAmount = 0;
    $component = new CRM_Businessdsa_BAO_Component();
    $component->is_active = 1;
    $component->accountable_advance = 1;
    $component->find();
    while ($component->fetch()) {
      $accountableAmount = $accountableAmount + $component->dsa_amount;
    }
    return $accountableAmount;
  }

  /**
   * Method to create Debit Business DSA activity and custom fields
   *
   * @param array $params (needs to have caseId, targetId, sourceId, noOfPersons, noOfDays)
   * @return bool if invalid params
   * @access public
   * @static
   */
  public static function createDebit($params) {
    if (!isset($params['caseId']) || !isset($params['targetId']) || !isset($params['sourceId']) || !isset($params['noOfPersons']) || !isset($params['noOfDays'])) {
      return FALSE;
    }
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $baseAmount = self::calculateBaseAmount() * $params['noOfDays'] * $params['noOfPersons'];
    $accountableAmount = self::calculateAccountableAmount() * $params['noOfDays'] * $params['noOfPersons'];
    $bdsaAmount = $baseAmount + $accountableAmount;
    $subject = self::buildBusinessDsaActivitySubject($params['noOfPersons'], $params['noOfDays']);
    $activityParams = array(
      'activity_type_id' => $extensionConfig->getDebBdsaActivityTypeId(),
      'source_id' => $params['sourceId'],
      'subject' => $subject,
      'status_id' => $extensionConfig->getPayableActivityStatusValue(),
      'case_id' => $params['caseId']);
    $expertId = CRM_Threepeas_BAO_PumCaseRelation::getCaseExpert($params['caseId']);
    if (empty($expertId)) {
      $activityParams['target_id'] = $params['targetId'];
    } else {
      $activityParams['target_id'] = $expertId;
    }
    $createdActivity = civicrm_api3('Activity', 'Create', $activityParams);
    self::createBusinessDsaRecord($createdActivity['id'], $bdsaAmount, $params['noOfDays'], $params['noOfPersons'], 'D');
  }

  /**
   * Method to create record in custom group for business dsa activity
   *
   * @param int $activityId
   * @param int $bdsaAmount
   * @param int $noOfDays
   * @param int $noOfPersons
   * @access protected
   * @static
   */
  protected static function createBusinessDsaRecord($activityId, $bdsaAmount, $noOfDays, $noOfPersons) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();

    $insertClauses[] = 'entity_id = %1';
    $insertClauses[] = $extensionConfig->getBdsaAmountCustomFieldColumn().' = %2';
    $insertClauses[] = $extensionConfig->getBdsaNoOfPersonsCustomFieldColumn().' = %3';
    $insertClauses[] = $extensionConfig->getBdsaNoOfDaysCustomFieldColumn().' = %4';

    $insertParams = array(
      1 => array($activityId, 'Integer'),
      2 => array($bdsaAmount, 'Integer'),
      3 => array($noOfPersons, 'Integer'),
      4 => array($noOfDays, 'Integer'));

    $query = 'INSERT INTO '.$extensionConfig->getBdsaCustomGroupTable().' SET '.implode(', ', $insertClauses);
    CRM_Core_DAO::executeQuery($query, $insertParams);
  }

  /**
   * Method to update the subject for the activity
   *
   * @param int $noOfPersons
   * @param int $noOfDays
   * @return string $subjectString
   * @access protected
   * @static
   */
  protected static function buildBusinessDsaActivitySubject($noOfPersons, $noOfDays) {
    $baseAmount = self::calculateBaseAmount() * $noOfDays * $noOfPersons;
    $accountableAmount = self::calculateAccountableAmount() * $noOfDays * $noOfPersons;
    $subjectString = 'Business DSA (Base amount '.CRM_Utils_Money::format($baseAmount).' and accountable amount '.CRM_Utils_Money::format($accountableAmount).', '.$noOfPersons.' persons and '.$noOfDays.' days)';

    return $subjectString;
  }

  /**
   * Method to set the activity types list for the CaseView summary for Business
   *
   * @param $form
   * @access public
   * @static
   */
  public static function modifyFormActivityTypesList(&$form) {
    $typeIndex = $form->_elementIndex['activity_type_id'];
    self::removeInvalidActivityTypeIdsFromList($form->_elements[$typeIndex]->_options, $form->_caseID);
  }

  /**
   * Method to remove unwanted list options for activity types
   *
   * @param array $typeOptions
   * @param int $caseId
   * @access protected
   * @static
   */
  protected static function removeInvalidActivityTypeIdsFromList(&$typeOptions, $caseId) {
    $typeIdsToBeRemoved = self::getActivityTypeIdsToBeRemoved($caseId);
    foreach ($typeOptions as $typeOptionId => $typeOption) {
      if (in_array($typeOption['attr']['value'], $typeIdsToBeRemoved)) {
        unset($typeOptions[$typeOptionId]);
      }
    }
  }

  /**
   * Method to get the activity type ids to be removed from form list
   *
   * @param int $caseId
   * @return array $typeIdsToBeRemoved
   * @access protected
   * @static
   */
  protected static function getActivityTypeIdsToBeRemoved($caseId) {
    $typeIdsToBeRemoved = array();
    $extensionConfig = CRM_Businessdsa_Config::singleton();

    if (!CRM_Core_Permission::check('edit DSA activity')) {
      $typeIdsToBeRemoved[] = $extensionConfig->getCredBdsaActivityTypeId();
      $typeIdsToBeRemoved[] = $extensionConfig->getDebBdsaActivityTypeId();
    } else {
      if (!self::dsaCanBeCredited($caseId)) {
        $typeIdsToBeRemoved[] = $extensionConfig->getCredBdsaActivityTypeId();
      }
      if (!self::dsaCanBeDebited($caseId)) {
        $typeIdsToBeRemoved[] = $extensionConfig->getDebBdsaActivityTypeId();
      }
    }
    return $typeIdsToBeRemoved;
  }

  /**
   * Method to determine if the business DSA on the case can be credited
   * This can only be done if there is a debet business dsa activity on the case
   *
   * @param int $caseId
   * @return bool
   * @access protected
   * @static
   */
  protected static function dsaCanBeCredited($caseId) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $debitParams = array(
      'case_id' => $caseId,
      'activity_type_id' => $extensionConfig->getDebBdsaActivityTypeId());
    $activityCount = civicrm_api3('CaseActivity', 'Getcount', $debitParams);
    if ($activityCount > 0) {
      return TRUE;
    }
    return FALSE;
  }
  /**
   * Method to determine if the debet business DSA on the case can be created
   * (only if not exists yet)
   *
   * @param int $caseId
   * @return bool
   * @access protected
   * @static
   */
  protected static function dsaCanBeDebited($caseId) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $debitParams = array(
      'case_id' => $caseId,
      'activity_type_id' => $extensionConfig->getDebBdsaActivityTypeId());
    $activityCount = civicrm_api3('CaseActivity', 'Getcount', $debitParams);
    if ($activityCount == 0) {
      if (CRM_Core_Permission::check('create DSA activity')) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Method to credit business DSA (delete if status = Payable, credit if status = Paid
   *
   * @param int $caseId
   * @access public
   * @static
   */
  public static function createCredit($caseId) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $debitParams = array(
      'case_id' => $caseId,
      'activity_type_id' => $extensionConfig->getDebBdsaActivityTypeId());
    $debitActivity = civicrm_api3('CaseActivity', 'Getsingle', $debitParams);
    if ($debitActivity['status_id'] == $extensionConfig->getPayableActivityStatusValue()) {
      civicrm_api3('Activity', 'Delete', array('id' => $debitActivity['activity_id']));
    } else {
      $creditParams = array(
        'id' => $debitActivity['activity_id'],
        'activity_type_id' => $extensionConfig->getCredBdsaActivityTypeId(),
        'status_id' => $extensionConfig->getPayableActivityStatusValue());
      civicrm_api3('Activity', 'Create', $creditParams);
    }
  }

  /**
   * Method to retrieve payable Bdsa's (possibly within a given date range)
   *
   * @param string $fromDate
   * @param string $toDate
   * @return array $payableBdsa
   * @access public
   * @static
   */
  public static function getPayableBdsa($fromDate, $toDate) {
    $payableBdsa = array();
    if (self::validExportParams($fromDate, $toDate) == TRUE) {
      $query = self::buildExportQuery($fromDate, $toDate);
      $queryParams = self::buildExportQueryParams($fromDate, $toDate);
      $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
      while ($dao->fetch()) {
        $payableBdsa[] = self::formatExportPayment($dao);
        self::setBdsaToPaid($dao->bdsa_activity_id);
      }
    }
    return $payableBdsa;
  }

  /**
   * Method to set hte activity status to paid
   *
   * @param int $activityId
   * @access private
   * @static
   */
  private static function setBdsaToPaid($activityId) {
    if (!empty($activityId)) {
      $extensionConfig = CRM_Businessdsa_Config::singleton();
      $params = array(
        'id' => $activityId,
        'status_id' => $extensionConfig->getPaidActivityStatusValue());
      civicrm_api3('Activity', 'Create', $params);
    }
  }

  /**
   * Method to format the data into the exportable string format for PUM FA
   *
   * @param object $dao
   * @return string $paymentLine
   * @throws Exception if error from one of the API calls
   * @access private
   * @static
   */
  private static function formatExportPayment($dao) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $paymentLine = array();
    /*
     * generic payment data
     */
    $paymentLine['Boekjaar'] = date('y');
    $paymentLine['Dagboek'] = 'I1';
    $paymentLine['Periode'] = date('m');
    $paymentLine['Boekstuk'] = self::getSequence('payment_line');
    $paymentLine['GrootboekNr'] = $extensionConfig->getBdsaGlValue();
    $paymentLine['Sponsorcode'] = CRM_Businessdsa_Utils::getDonorCode(CRM_Threepeas_BAO_PumDonorLink::getCaseDonor($dao->bdsa_case_id));
    $paymentLine['Datum'] = date('d-m-Y');
    $paymentLine['Bedrag'] = '0000000000';
    $paymentLine['Filler1'] = ' ';
    $paymentLine['Filler2'] = ' ';
    $paymentLine['FactuurNrRunType'] = 'D';
    $paymentLine['FactuurNrYear'] = date('y', strtotime($dao->bdsa_activity_date));
    $paymentLine['FactuurNr'] = self::getSequence('invoice_number');
    $paymentLine['FactuurNrAmtType'] = 'x';
    $paymentLine['FactuurDatum'] = date('d-m-Y', strtotime($dao->bdsa_activity_date));
    $bdsaAmountColumn = $extensionConfig->getBdsaAmountCustomFieldColumn();
    $paymentLine['FactuurBedrag'] = CRM_Businessdsa_Utils::formatAmountForExport($dao->$bdsaAmountColumn);
    $paymentLine['ValutaCode'] = 'EUR';
    $paymentLine['Taal'] = 'N';
    /*
     * data based on credit or debit
     */
    if ($dao->bdsa_activity_type_id == $extensionConfig->getDebBdsaActivityTypeId()) {
      $paymentLine['DC'] = 'D';
      $paymentLine['PlusMin'] = '+';
      $paymentLine['FactuurPlusMin'] = '+';
    } else {
      $paymentLine['DC'] = 'C';
      $paymentLine['PlusMin'] = '-';
      $paymentLine['FactuurPlusMin'] = '-';
    }
    /*
     * all data from custom group pum case number
     */
    $pumCaseData = CRM_Businessdsa_Utils::getPumCaseData($dao->bdsa_case_id);
    $paymentLine['Kostenplaats'] = $pumCaseData['Case_country'].$pumCaseData['Case_sequence'].$pumCaseData['Case_type'];
    $paymentLine['Kostendrager'] = $pumCaseData['Case_country'];
    $paymentLine['OmschrijvingB'] = $pumCaseData['Case_sequence'];
    $paymentLine['OmschrijvingC'] = $pumCaseData['Case_country'];
    $paymentLine['Kenmerk'] = $pumCaseData['Case_sequence'].$pumCaseData['Case_country'];
    $paymentLine['Soort'] = $pumCaseData['Case_type'];
    /*
     * data from Expert
     */
    $caseExpertId = CRM_Threepeas_BAO_PumCaseRelation::getCaseExpert($dao->bdsa_case_id);
    $expertData = civicrm_api3('Contact', 'Getsingle', array('id' => $caseExpertId));
    $paymentLine['OmschrijvingA'] = $expertData['last_name'];
    $paymentLine['CrediteurNr'] = CRM_Businessdsa_Utils::getShortnameForContact($caseExpertId);
    $paymentLine['Shortname'] = $paymentLine['CrediteurNr'];
    $naamOrganisatie = '';
    if (isset($expertData['middle_name']) && !empty($expertData['middle_name'])) {
      $naamOrganisatie = $expertData['middle_name'] . ' ';
    }
    $naamOrganisatie .= $expertData['last_name'].' '.$expertData['first_name'];
    $paymentLine['NaamOrganisatie'] = $naamOrganisatie;
    $paymentLine['Land'] = CRM_Businessdsa_Utils::getCountryIsoCode($expertData['country_id']);
    $paymentLine['Adres1'] = $expertData['street_address'];
    $paymentLine['Adres2'] = $expertData['postal_code'].' '.$expertData['city'];
    /*
     * bankData van expert
     */
    $bankData = CRM_Businessdsa_Utils::getExpertBankData($caseExpertId);
    $paymentLine['BankRekNr'] = $bankData['Bank_Account_Number'];
    $paymentLine['Rekeninghouder'] = $bankData['Accountholder_name'];
    $paymentLine['RekeninghouderLand'] = $bankData['Accountholder_country'];
    $paymentLine['RekeninghouderAdres1'] = $bankData['Accountholder_address'];
    $paymentLine['RekeninghouderAdres2'] = $bankData['Accountholder_postal_code'].' '.$bankData['Accountholder_city'];
    $paymentLine['IBAN'] = $bankData['IBAN_nummer'];
    $paymentLine['Banknaam'] = $bankData['Bank_Name'];
    $paymentLine['BankPlaats'] = $bankData['Bank_City'];
    $paymentLine['BankLand'] = $bankData['Bank_Country_ISO_Code'];
    $paymentLine['BIC'] = $bankData['BIC_Swiftcode'];
    $formattedOutput = CRM_Dsa_Utils::dsa_concatValues($paymentLine);
    return $formattedOutput;
  }

  /**
   * Method to get a sequence and fill it up
   *
   * @param string $sequenceName
   * @return bool|string
   * @access private
   * @static
   */
  private static function getSequence($sequenceName) {
    if (empty($sequenceName)) {
    return FALSE;
    }
    $sequence = civicrm_api3('Sequence', 'Nextval', array('name' => $sequenceName));
    return $sequence['values'];
  }

  /**
   * Method to validate the parameters coming in the getPayablesBdsa method
   *
   * @param string $fromDate
   * @param string $toDate
   * @return boolean
   * @access private
   * @static
   *
   */
  private static function validExportParams($fromDate, $toDate) {
    if (!empty($fromDate) && CRM_Businessdsa_Utils::isValidDate($fromDate) == FALSE) {
      return FALSE;
    }
    if (!empty($toDate) && CRM_Businessdsa_Utils::isValidDate($toDate) == FALSE) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Method to build the query required for retrieving the payable dsa
   *
   * @param string $fromDate
   * @param string $toDate
   * @return string
   * @access private
   * @static
   */
  private static function buildExportQuery($fromDate, $toDate) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $select = 'SELECT bdsa.*, act.id AS bdsa_activity_id, act.activity_type_id AS bdsa_activity_type_id,
      ccase.id AS bdsa_case_id, contcase.contact_id AS bdsa_contact_id, act.activity_date_time AS bdsa_activity_date
      FROM '.$extensionConfig->getBdsaCustomGroupTable().' bdsa
      JOIN civicrm_activity act ON bdsa.entity_id = act.id
      JOIN civicrm_case_activity caseact ON act.id = caseact.activity_id
      JOIN civicrm_case_contact contcase ON caseact.case_id = contcase.case_id
      JOIN civicrm_case ccase ON caseact.case_id = ccase.id';
    $whereClauses = array();
    $whereClauses[] = 'act.activity_type_id IN(%1, %2)';
    $whereClauses[] = 'act.status_id = %3';
    $whereClauses[] = 'ccase.is_deleted = %4';
    if (!empty($fromDate)) {
      $whereClauses[] = 'act.activity_date_time >= %5';
    }
    if (!empty($toDate)) {
      $whereClauses[] = 'act.activity_date_time <= %6';
    }
    return $select.' WHERE '.implode(' AND ', $whereClauses);
  }

  /**
   * MEthod to build the array with query parameters for retrieving the payable dsa
   *
   * @param string $fromDate
   * @param string $toDate
   * @return array $queryParams
   * @access private
   * @static
   */
  private static function buildExportQueryParams($fromDate, $toDate) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $queryParams = array(
      1 => array($extensionConfig->getDebBdsaActivityTypeId(), 'Integer'),
      2 => array($extensionConfig->getCredBdsaActivityTypeId(), 'Integer'),
      3 => array($extensionConfig->getPayableActivityStatusValue(), 'Integer'),
      4 => array(0, 'Integer'));
    if (!empty($fromDate)) {
      $queryParams[5] = array(date('Ymd', strtotime($fromDate)), 'Date');
    }
    if (!empty($toDate)) {
      $queryParams[6] = array(date('Ymd', strtotime($toDate)), 'Date');
    }
    return $queryParams;
  }
  public static function getExpertBdsa($expertId) {
    if (empty($expertId)) {
      return array();
    }
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    $expectBdsas = array();
    $actQuery = 'SELECT activity_id, label AS status, status_id, bdsa.*
      FROM civicrm_activity_contact
      JOIN civicrm_activity act ON activity_id = act.id
      JOIN civicrm_option_value ON value = status_id AND option_group_id = %1
      LEFT JOIN '.$extensionConfig->getBdsaCustomGroupTable().' bdsa ON activity_id = entity_id
      WHERE is_current_revision = %2 AND is_deleted = %3 AND record_type_id = %4
      AND contact_id = %5 AND activity_type_id IN(%6, %7)';
    $actParams = array(
      1 => array(CRM_Businessdsa_Utils::getOptionGroupIdWithName('activity_status'), 'Integer'),
      2 => array(1, 'Integer'),
      3 => array(0, 'Integer'),
      4 => array(3, 'Integer'),
      5 => array($expertId, 'Integer'),
      6 => array($extensionConfig->getCredBdsaActivityTypeId(), 'Integer'),
      7 => array($extensionConfig->getDebBdsaActivityTypeId(), 'Integer'));
    $daoAct = CRM_Core_DAO::executeQuery($actQuery, $actParams);
    while ($daoAct->fetch()) {
      $daoProperties = get_object_vars($daoAct);
      foreach ($daoProperties as $key => $value) {
        if ($key != 'N' && $key != 'id' && substr($key,0,1) != '_') {
          $result[$key] = $value;
        }
      }
      $expectBdsas[$result['activity_id']] = $result;
    }
    return $expectBdsas;
  }

  /**
   * Method to check if the activity type is a dsa type activity.
   *
   * @param int $activityTypeId
   * @return bool
   * @access public
   * @static
   */
  public static function isDsaActivityType($activityTypeId) {
    $extensionConfig = CRM_Businessdsa_Config::singleton();
    if ($activityTypeId == $extensionConfig->getDebBdsaActivityTypeId()
      || $activityTypeId == $extensionConfig->getCredBdsaActivityTypeId()
      || CRM_Dsa_Utils::isDsaActivityType($activityTypeId) == TRUE) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Method is a copy of the CRM_Case_BAO_Case method in core but
   * does not build actions for DSA type activities
   *
   * @param int $caseID
   * @param array $params
   * @param int $contactID
   * @param string $context
   * @param int $userID
   * @param int $type
   * @return array
   */
  public static function getCaseActivity($caseID, &$params, $contactID, $context = NULL, $userID = NULL, $type = NULL) {
    $values = array();
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    // CRM-5081 - formatting the dates to omit seconds.
    // Note the 00 in the date format string is needed otherwise later on it thinks scheduled ones are overdue.
    $select = "SELECT count(ca.id) as ismultiple, ca.id as id,
                          ca.activity_type_id as type,
                          ca.activity_type_id as activity_type_id,
                          cc.sort_name as reporter,
                          cc.id as reporter_id,
                          acc.sort_name AS assignee,
                          acc.id AS assignee_id,
                          DATE_FORMAT(IF(ca.activity_date_time < NOW() AND ca.status_id=ov.value,
                            ca.activity_date_time,
                            DATE_ADD(NOW(), INTERVAL 1 YEAR)
                          ), '%Y%m%d%H%i00') as overdue_date,
                          DATE_FORMAT(ca.activity_date_time, '%Y%m%d%H%i00') as display_date,
                          ca.status_id as status,
                          ca.subject as subject,
                          ca.is_deleted as deleted,
                          ca.priority_id as priority,
                          ca.weight as weight,
                          GROUP_CONCAT(ef.file_id) as attachment_ids ";

    $from = "
      FROM civicrm_case_activity cca
                  INNER JOIN civicrm_activity ca ON ca.id = cca.activity_id
                  INNER JOIN civicrm_activity_contact cac ON cac.activity_id = ca.id AND cac.record_type_id = {$sourceID}
                  INNER JOIN civicrm_contact cc ON cc.id = cac.contact_id
                  INNER JOIN civicrm_option_group cog ON cog.name = 'activity_type'
                  INNER JOIN civicrm_option_value cov ON cov.option_group_id = cog.id
                         AND cov.value = ca.activity_type_id AND cov.is_active = 1
                  LEFT JOIN civicrm_entity_file ef on ef.entity_table = 'civicrm_activity'  AND ef.entity_id = ca.id
                  LEFT OUTER JOIN civicrm_option_group og ON og.name = 'activity_status'
                  LEFT OUTER JOIN civicrm_option_value ov ON ov.option_group_id=og.id AND ov.name = 'Scheduled'
                  LEFT JOIN civicrm_activity_contact caa
                                ON caa.activity_id = ca.id AND caa.record_type_id = {$assigneeID}
                  LEFT JOIN civicrm_contact acc ON acc.id = caa.contact_id  ";

    $where = 'WHERE cca.case_id= %1
                    AND ca.is_current_revision = 1';

    if (CRM_Utils_Array::value('reporter_id', $params)) {
      $where .= " AND cac.contact_id = " . CRM_Utils_Type::escape($params['reporter_id'], 'Integer');
    }

    if (CRM_Utils_Array::value('status_id', $params)) {
      $where .= " AND ca.status_id = " . CRM_Utils_Type::escape($params['status_id'], 'Integer');
    }

    if (CRM_Utils_Array::value('activity_deleted', $params)) {
      $where .= " AND ca.is_deleted = 1";
    }
    else {
      $where .= " AND ca.is_deleted = 0";
    }

    if (CRM_Utils_Array::value('activity_type_id', $params)) {
      $where .= " AND ca.activity_type_id = " . CRM_Utils_Type::escape($params['activity_type_id'], 'Integer');
    }

    if (CRM_Utils_Array::value('activity_date_low', $params)) {
      $fromActivityDate = CRM_Utils_Type::escape(CRM_Utils_Date::processDate($params['activity_date_low']), 'Date');
    }
    if (CRM_Utils_Array::value('activity_date_high', $params)) {
      $toActivityDate = CRM_Utils_Type::escape(CRM_Utils_Date::processDate($params['activity_date_high']), 'Date');
      $toActivityDate = $toActivityDate ? $toActivityDate + 235959 : NULL;
    }

    if (!empty($fromActivityDate)) {
      $where .= " AND ca.activity_date_time >= '{$fromActivityDate}'";
    }

    if (!empty($toActivityDate)) {
      $where .= " AND ca.activity_date_time <= '{$toActivityDate}'";
    }

    // hack to handle to allow initial sorting to be done by query
    if (CRM_Utils_Array::value('sortname', $params) == 'undefined') {
      $params['sortname'] = NULL;
    }

    if (CRM_Utils_Array::value('sortorder', $params) == 'undefined') {
      $params['sortorder'] = NULL;
    }

    $sortname = CRM_Utils_Array::value('sortname', $params);
    $sortorder = CRM_Utils_Array::value('sortorder', $params);

    $groupBy = " GROUP BY ca.id ";

    if (!$sortname AND !$sortorder) {
      // CRM-5081 - added id to act like creation date
      $orderBy = " ORDER BY overdue_date ASC, display_date DESC, weight DESC";
    }
    else {
      $sort = "{$sortname} {$sortorder}";
      $sort = CRM_Utils_Type::escape($sort, 'String');
      $orderBy = " ORDER BY $sort ";
      if ($sortname != 'display_date') {
        $orderBy .= ', display_date DESC';
      }
    }

    $page = CRM_Utils_Array::value('page', $params);
    $rp = CRM_Utils_Array::value('rp', $params);

    if (!$page) {
      $page = 1;
    }
    if (!$rp) {
      $rp = 10;
    }

    $start = (($page - 1) * $rp);
    $query = $select . $from . $where . $groupBy . $orderBy;

    $params = array(1 => array($caseID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $params['total'] = $dao->N;

    //FIXME: need to optimize/cache these queries
    $limit = " LIMIT $start, $rp";
    $query .= $limit;

    //EXIT;
    $dao = CRM_Core_DAO::executeQuery($query, $params);


    $activityTypes = CRM_Case_PseudoConstant::caseActivityType(FALSE, TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $activityPriority = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');

    $url = CRM_Utils_System::url("civicrm/case/activity",
      "reset=1&cid={$contactID}&caseid={$caseID}", FALSE, NULL, FALSE
    );

    $contextUrl = '';
    if ($context == 'fulltext') {
      $contextUrl = "&context={$context}";
    }
    $editUrl = "{$url}&action=update{$contextUrl}";
    $deleteUrl = "{$url}&action=delete{$contextUrl}";
    $restoreUrl = "{$url}&action=renew{$contextUrl}";
    $viewTitle = ts('View this activity.');
    $statusTitle = ts('Edit status');

    $emailActivityTypeIDs = array(
      'Email' => CRM_Core_OptionGroup::getValue('activity_type',
        'Email',
        'name'
      ),
      'Inbound Email' => CRM_Core_OptionGroup::getValue('activity_type',
        'Inbound Email',
        'name'
      ),
    );

    $emailActivityTypeIDs = array(
      'Email' => CRM_Core_OptionGroup::getValue('activity_type',
        'Email',
        'name'
      ),
      'Inbound Email' => CRM_Core_OptionGroup::getValue('activity_type',
        'Inbound Email',
        'name'
      ),
    );

    $caseDeleted = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $caseID, 'is_deleted');

    // define statuses which are handled like Completed status (others are assumed to be handled like Scheduled status)
    $compStatusValues = array();
    $compStatusNames = array('Completed', 'Left Message', 'Cancelled', 'Unreachable', 'Not Required');
    foreach ($compStatusNames as $name) {
      $compStatusValues[] = CRM_Core_OptionGroup::getValue('activity_status', $name, 'name');
    }
    $contactViewUrl = CRM_Utils_System::url("civicrm/contact/view",
      "reset=1&cid=", FALSE, NULL, FALSE
    );
    $hasViewContact = CRM_Core_Permission::giveMeAllACLs();
    $clientIds = CRM_Case_BAO_Case::retrieveContactIdsByCaseId($caseID);

    if (!$userID) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
    }

    while ($dao->fetch()) {

      $allowView = CRM_Case_BAO_Case::checkPermission($dao->id, 'view', $dao->activity_type_id, $userID);
      $allowEdit = CRM_Case_BAO_Case::checkPermission($dao->id, 'edit', $dao->activity_type_id, $userID);
      $allowDelete = CRM_Case_BAO_Case::checkPermission($dao->id, 'delete', $dao->activity_type_id, $userID);

      //do not have sufficient permission
      //to access given case activity record.
      if (!$allowView && !$allowEdit && !$allowDelete) {
        continue;
      }

      /*
       * no permissions for DSA activities
       */
      if (self::isDsaActivityType($dao->type)) {
        $allowEdit = FALSE;
        $allowDelete = FALSE;
      }

      $values[$dao->id]['id'] = $dao->id;
      $values[$dao->id]['type'] = $activityTypes[$dao->type]['label'];

      $reporterName = $dao->reporter;
      if ($hasViewContact) {
        $reporterName = '<a href="' . $contactViewUrl . $dao->reporter_id . '">' . $dao->reporter . '</a>';
      }
      $values[$dao->id]['reporter'] = $reporterName;
      $targetNames = CRM_Activity_BAO_ActivityContact::getNames($dao->id, $targetID);
      $targetContactUrls = $withContacts = array();
      foreach ($targetNames as $targetId => $targetName) {
        if (!in_array($targetId, $clientIds)) {
          $withContacts[$targetId] = $targetName;
        }
      }
      foreach ($withContacts as $cid => $name) {
        if ($hasViewContact) {
          $name = '<a href="' . $contactViewUrl . $cid . '">' . $name . '</a>';
        }
        $targetContactUrls[] = $name;
      }
      $values[$dao->id]['with_contacts'] = implode('; ', $targetContactUrls);

      $values[$dao->id]['display_date'] = CRM_Utils_Date::customFormat($dao->display_date);
      $values[$dao->id]['status'] = $activityStatus[$dao->status];

      //check for view activity.
      $subject = (empty($dao->subject)) ? '(' . ts('no subject') . ')' : $dao->subject;
      if ($allowView) {
        $subject = '<a href="javascript:' . $type . 'viewActivity(' . $dao->id . ',' . $contactID . ',' . '\'' . $type . '\' );" title=\'' . $viewTitle . '\'>' . $subject . '</a>';
      }
      $values[$dao->id]['subject'] = $subject;

      // add activity assignee to activity selector. CRM-4485.
      if (isset($dao->assignee)) {
        if ($dao->ismultiple == 1) {
          if ($dao->reporter_id != $dao->assignee_id) {
            $values[$dao->id]['reporter'] .= ($hasViewContact) ? ' / ' . "<a href='{$contactViewUrl}{$dao->assignee_id}'>$dao->assignee</a>" : ' / ' . $dao->assignee;
          }
          $values[$dao->id]['assignee'] = $dao->assignee;
        }
        else {
          $values[$dao->id]['reporter'] .= ' / ' . ts('(multiple)');
        }
      }
      $url = "";
      $additionalUrl = "&id={$dao->id}";
      if (!$dao->deleted) {
        //hide edit link of activity type email.CRM-4530.
        if (!in_array($dao->type, $emailActivityTypeIDs)) {
          //hide Edit link if activity type is NOT editable (special case activities).CRM-5871
          if ($allowEdit) {
            $url = '<a href="' . $editUrl . $additionalUrl . '">' . ts('Edit') . '</a> ';
          }
        }
        if ($allowDelete) {
          if (!empty($url)) {
            $url .= " | ";
          }
          $url .= '<a href="' . $deleteUrl . $additionalUrl . '">' . ts('Delete') . '</a>';
        }
      }
      elseif (!$caseDeleted) {
        $url = '<a href="' . $restoreUrl . $additionalUrl . '">' . ts('Restore') . '</a>';
        $values[$dao->id]['status'] = $values[$dao->id]['status'] . '<br /> (deleted)';
      }

      //check for operations.
      /*
       * only if not a DSA activity
       */
      if (!self::isDsaActivityType($dao->type)) {
        if (CRM_Case_BAO_Case::checkPermission($dao->id, 'Move To Case', $dao->activity_type_id)) {
          $url .= " | " . '<a href="#" onClick="Javascript:fileOnCase( \'move\',' . $dao->id . ', ' . $caseID . ' ); return false;">' . ts('Move To Case') . '</a> ';
        }
        if (CRM_Case_BAO_Case::checkPermission($dao->id, 'Copy To Case', $dao->activity_type_id)) {
          $url .= " | " . '<a href="#" onClick="Javascript:fileOnCase( \'copy\',' . $dao->id . ',' . $caseID . ' ); return false;">' . ts('Copy To Case') . '</a> ';
        }
      }
      // if there are file attachments we will return how many and, if only one, add a link to it
      if (!empty($dao->attachment_ids)) {
        $attachmentIDs = explode(',', $dao->attachment_ids);
        $values[$dao->id]['no_attachments'] = count($attachmentIDs);
        if ($values[$dao->id]['no_attachments'] == 1) {
          // if there is only one it's easy to do a link - otherwise just flag it
          $attachmentViewUrl = CRM_Utils_System::url(
            "civicrm/file",
            "reset=1&eid=" . $dao->id . "&id=" . $dao->attachment_ids,
            FALSE,
            NULL,
            FALSE
          );
          $url .= " | " . "<a href=$attachmentViewUrl >" . ts('View Attachment') . '</a> ';
        }
      }


      $values[$dao->id]['links'] = $url;
      $values[$dao->id]['class'] = "";

      if (!empty($dao->priority)) {
        if ($dao->priority == CRM_Core_OptionGroup::getValue('priority', 'Urgent', 'name')) {
          $values[$dao->id]['class'] = $values[$dao->id]['class'] . "priority-urgent ";
        }
        elseif ($dao->priority == CRM_Core_OptionGroup::getValue('priority', 'Low', 'name')) {
          $values[$dao->id]['class'] = $values[$dao->id]['class'] . "priority-low ";
        }
      }

      if (CRM_Utils_Array::crmInArray($dao->status, $compStatusValues)) {
        $values[$dao->id]['class'] = $values[$dao->id]['class'] . " status-completed";
      }
      else {
        if (CRM_Utils_Date::overdue($dao->display_date)) {
          $values[$dao->id]['class'] = $values[$dao->id]['class'] . " status-overdue";
        }
        else {
          $values[$dao->id]['class'] = $values[$dao->id]['class'] . " status-scheduled";
        }
      }

      if ($allowEdit) {
        $values[$dao->id]['status'] = '<a class="crm-activity-status crm-activity-status-' . $dao->id . ' ' . $values[$dao->id]['class'] . ' crm-activity-change-status crm-editable-enabled" activity_id=' . $dao->id . ' current_status=' . $dao->status . ' case_id=' . $caseID . '" href="#" title=\'' . $statusTitle . '\'>' . $values[$dao->id]['status'] . '</a>';
      }
    }
    $dao->free();

    return $values;
  }
}
