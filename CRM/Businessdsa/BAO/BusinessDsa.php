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
      'target_id' => $params['targetId'],
      'source_id' => $params['sourceId'],
      'subject' => $subject,
      'status_id' => $extensionConfig->getPayableActivityStatusValue(),
      'case_id' => $params['caseId']);
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

    if (!CRM_Core_Permission::check('edit DSA Activity')) {
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
      if (CRM_Core_Permission::check('create DSA Activity')) {
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
    $debitActivity = civicrm_api3('Activity', 'Getsingle', $debitParams);
    if ($debitActivity['status_id'] == $extensionConfig->getPayableActivityStatusValue()) {
      civicrm_api3('Activity', 'Delete', array('id' => $debitActivity['id']));
    } else {
      $creditParams = array(
        'id' => $debitActivity['id'],
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
}
