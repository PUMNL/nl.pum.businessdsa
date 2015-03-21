CREATE TABLE IF NOT EXISTS civicrm_business_dsa_component (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(75) NULL,
  description TEXT NULL,
  dsa_amount INT NULL,
  accountable_advance TINYINT NULL DEFAULT 0,
  is_active TINYINT NULL DEFAULT 1,
  modified_date DATE NULL,
  modified_user_id INT NULL,
  created_date DATE NULL,
  created_user_id INT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX id_UNIQUE (id ASC))
  ENGINE = InnoDB;