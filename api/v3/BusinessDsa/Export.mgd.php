<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'Cron:BusinessDsa.Export',
    'entity' => 'Job',
    'params' => 
    array (
      'version' => 3,
      'name' => 'Export Business DSA in date range to FA',
      'description' => 'Retrieve all Business DSA data in date range and format as flat string array',
      'run_frequency' => 'Daily',
      'api_entity' => 'BusinessDsa',
      'api_action' => 'Export',
      'parameters' => 'from_date = [select Business DSA on and after this date] required / to_date = [select Business DSA on and before this date] required',
      'is_active' => 0
    ),
  ),
);