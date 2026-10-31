#
# $Id: upgrade_latest.sql 6177 2012-08-14 07:51:05Z ajdonnison $
#
# DO NOT USE THIS SCRIPT DIRECTLY - USE THE INSTALLER INSTEAD.
#
# All entries must be date stamped in the correct format.
#

# Extend value_charvalue from 250 to 1000 characters
ALTER TABLE `%dbprefix%custom_fields_values` MODIFY `value_charvalue` `value_charvalue` VARCHAR( 1000 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

# 20261009
# Widen user_password for password_hash() output and add password reset token columns
ALTER TABLE `%dbprefix%users` MODIFY `user_password` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `%dbprefix%users` ADD `user_reset_token` VARCHAR(255) NULL DEFAULT NULL, ADD `user_reset_expiry` DATETIME NULL DEFAULT NULL;

# Move the phpGACL id sequences past the existing ids. Fresh installs seeded the gacl tables
# without them, so module installs and new users failed with duplicate keys.
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_acl_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_acl_seq`;
INSERT INTO `%dbprefix%gacl_acl_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_acl`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_aco_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_aco_seq`;
INSERT INTO `%dbprefix%gacl_aco_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_aco`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_aro_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_aro_seq`;
INSERT INTO `%dbprefix%gacl_aro_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_aro`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_axo_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_axo_seq`;
INSERT INTO `%dbprefix%gacl_axo_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_axo`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_aco_sections_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_aco_sections_seq`;
INSERT INTO `%dbprefix%gacl_aco_sections_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_aco_sections`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_aro_sections_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_aro_sections_seq`;
INSERT INTO `%dbprefix%gacl_aro_sections_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_aro_sections`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_axo_sections_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_axo_sections_seq`;
INSERT INTO `%dbprefix%gacl_axo_sections_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_axo_sections`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_aro_groups_id_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_aro_groups_id_seq`;
INSERT INTO `%dbprefix%gacl_aro_groups_id_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_aro_groups`;
CREATE TABLE IF NOT EXISTS `%dbprefix%gacl_axo_groups_id_seq` (`id` int(11) NOT NULL);
DELETE FROM `%dbprefix%gacl_axo_groups_id_seq`;
INSERT INTO `%dbprefix%gacl_axo_groups_id_seq` (`id`) SELECT COALESCE(MAX(`id`), 0) FROM `%dbprefix%gacl_axo_groups`;

# 20261030
# The project is now called dotPulse: rename the page title and mail subject prefix if they were never changed
UPDATE `%dbprefix%config` SET `config_value` = 'dotPulse' WHERE `config_name` = 'page_title' AND `config_value` = 'dotProject';
UPDATE `%dbprefix%config` SET `config_value` = '[dotPulse]' WHERE `config_name` = 'email_prefix' AND `config_value` = '[dotProject]';

# 20261031
# Mail: a sender address for all mail, and a preference for task assignment emails (on by default)
INSERT INTO `%dbprefix%config` (`config_name`, `config_value`, `config_group`, `config_type`)
  SELECT 'mail_from', '', 'mail', 'text' FROM DUAL
  WHERE NOT EXISTS (SELECT 1 FROM `%dbprefix%config` WHERE `config_name` = 'mail_from');
INSERT INTO `%dbprefix%user_preferences` (`pref_user`, `pref_name`, `pref_value`)
  SELECT 0, 'TASKASSIGNMAIL', '1' FROM DUAL
  WHERE NOT EXISTS (SELECT 1 FROM `%dbprefix%user_preferences` WHERE `pref_user` = 0 AND `pref_name` = 'TASKASSIGNMAIL');

# 20261101
# REST API: personal access tokens (only the SHA-256 of each token is stored), off until an administrator turns the API on
CREATE TABLE IF NOT EXISTS `%dbprefix%api_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `token_user` int(11) NOT NULL,
  `token_name` varchar(100) NOT NULL DEFAULT '',
  `token_hash` char(64) NOT NULL,
  `token_prefix` varchar(12) NOT NULL DEFAULT '',
  `token_scope` varchar(10) NOT NULL DEFAULT 'read',
  `token_created` datetime NOT NULL,
  `token_expires` datetime DEFAULT NULL,
  `token_last_used` datetime DEFAULT NULL,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `idx_token_hash` (`token_hash`),
  KEY `idx_token_user` (`token_user`)
);
INSERT INTO `%dbprefix%config` (`config_name`, `config_value`, `config_group`, `config_type`)
  SELECT 'api_enabled', 'false', 'api', 'checkbox' FROM DUAL
  WHERE NOT EXISTS (SELECT 1 FROM `%dbprefix%config` WHERE `config_name` = 'api_enabled');
