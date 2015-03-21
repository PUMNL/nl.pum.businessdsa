<?php

/**
 * Collection of upgrade steps
 */
class CRM_Businessdsa_Upgrader extends CRM_Businessdsa_Upgrader_Base {
  public function install() {
    $this->executeSqlFile('sql/createBusinessDSAComponent.sql');
  }
}
