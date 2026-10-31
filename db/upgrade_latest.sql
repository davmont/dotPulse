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

# 20261102
# Personal dashboard: a core module, first in the menu, in the "All" and "Non-Admin" module groups (so every
# role with rights on those groups gets it), and the landing page where the stock calendar day view still is
INSERT INTO `%dbprefix%modules` (`mod_name`, `mod_directory`, `mod_version`, `mod_setup_class`, `mod_type`, `mod_active`, `mod_ui_name`, `mod_ui_icon`, `mod_ui_order`, `mod_ui_active`, `mod_description`)
  SELECT 'Dashboard', 'dashboard', '1.0.0', '', 'core', 1, 'Dashboard', 'dashboard.png', 0, 1, 'Personal dashboard' FROM DUAL
  WHERE NOT EXISTS (SELECT 1 FROM `%dbprefix%modules` WHERE `mod_directory` = 'dashboard');
INSERT INTO `%dbprefix%gacl_axo` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`)
  SELECT COALESCE(MAX(`id`), 0) + 1, 'app', 'dashboard', 0, 'Dashboard', 0 FROM `%dbprefix%gacl_axo`
  HAVING NOT EXISTS (SELECT 1 FROM `%dbprefix%gacl_axo` WHERE `section_value` = 'app' AND `value` = 'dashboard');
UPDATE `%dbprefix%gacl_axo_seq` SET `id` = (SELECT MAX(`id`) FROM `%dbprefix%gacl_axo`);
INSERT INTO `%dbprefix%gacl_groups_axo_map` (`group_id`, `axo_id`)
  SELECT g.`id`, a.`id` FROM `%dbprefix%gacl_axo_groups` g, `%dbprefix%gacl_axo` a
  WHERE g.`value` IN ('all', 'non_admin') AND a.`section_value` = 'app' AND a.`value` = 'dashboard'
  AND NOT EXISTS (SELECT 1 FROM `%dbprefix%gacl_groups_axo_map` m WHERE m.`group_id` = g.`id` AND m.`axo_id` = a.`id`);
DELETE FROM `%dbprefix%dotpermissions` WHERE `section` = 'app' AND `axo` = 'dashboard';
INSERT INTO `%dbprefix%dotpermissions` (acl_id,user_id,section,axo,permission,allow,priority,enabled)
  SELECT acl.id,aro.value,axo.section_value,axo.value,aco_m.value,acl.allow,2,acl.enabled
  FROM `%dbprefix%gacl_acl` acl
  LEFT JOIN `%dbprefix%gacl_aco_map`        aco_m   ON acl.id=aco_m.acl_id
  LEFT JOIN `%dbprefix%gacl_aro_map`        aro_m   ON acl.id=aro_m.acl_id
  LEFT JOIN `%dbprefix%gacl_aro`            aro     ON aro_m.value=aro.value
  LEFT JOIN `%dbprefix%gacl_axo_groups_map` axo_gm  ON axo_gm.acl_id=acl.id
  LEFT JOIN `%dbprefix%gacl_axo_groups`     axo_g   ON axo_gm.group_id=axo_g.id
  LEFT JOIN `%dbprefix%gacl_groups_axo_map` g_axo_m ON axo_g.id=g_axo_m.group_id
  LEFT JOIN `%dbprefix%gacl_axo`            axo     ON g_axo_m.axo_id=axo.id
  WHERE aro.value IS NOT NULL AND axo_g.value IS NOT NULL AND axo.section_value = 'app' AND axo.value = 'dashboard';
INSERT INTO `%dbprefix%dotpermissions` (acl_id,user_id,section,axo,permission,allow,priority,enabled)
  SELECT acl.id,aro.value,axo.section_value,axo.value,aco_m.value,acl.allow,4,acl.enabled
  FROM `%dbprefix%gacl_acl` acl
  LEFT JOIN `%dbprefix%gacl_aco_map`        aco_m   ON acl.id=aco_m.acl_id
  LEFT JOIN `%dbprefix%gacl_aro_map`        aro_m   ON acl.id=aro_m.acl_id
  LEFT JOIN `%dbprefix%gacl_aro_groups_map` aro_gm  ON acl.id=aro_gm.acl_id
  LEFT JOIN `%dbprefix%gacl_aro_groups`     aro_g   ON aro_gm.group_id=aro_g.id
  LEFT JOIN `%dbprefix%gacl_axo_groups_map` axo_gm  ON axo_gm.acl_id=acl.id
  LEFT JOIN `%dbprefix%gacl_axo_groups`     axo_g   ON axo_gm.group_id=axo_g.id
  LEFT JOIN `%dbprefix%gacl_groups_aro_map` g_aro_m ON aro_g.id=g_aro_m.group_id
  LEFT JOIN `%dbprefix%gacl_aro`            aro     ON g_aro_m.aro_id=aro.id
  LEFT JOIN `%dbprefix%gacl_groups_axo_map` g_axo_m ON axo_g.id=g_axo_m.group_id
  LEFT JOIN `%dbprefix%gacl_axo`            axo     ON g_axo_m.axo_id=axo.id
  WHERE axo_g.value IS NOT NULL AND aro.value IS NOT NULL AND axo.section_value = 'app' AND axo.value = 'dashboard';
UPDATE `%dbprefix%config` SET `config_value` = 'dashboard' WHERE `config_name` = 'default_view_m' AND `config_value` = 'calendar'
  AND EXISTS (SELECT 1 FROM (SELECT `config_value` FROM `%dbprefix%config` WHERE `config_name` = 'default_view_a') a WHERE a.`config_value` = 'day_view');
UPDATE `%dbprefix%config` SET `config_value` = 'index' WHERE `config_name` = 'default_view_a' AND `config_value` = 'day_view'
  AND EXISTS (SELECT 1 FROM (SELECT `config_value` FROM `%dbprefix%config` WHERE `config_name` = 'default_view_m') m WHERE m.`config_value` = 'dashboard');
UPDATE `%dbprefix%config` SET `config_value` = '0' WHERE `config_name` = 'default_view_tab'
  AND EXISTS (SELECT 1 FROM (SELECT `config_value` FROM `%dbprefix%config` WHERE `config_name` = 'default_view_m') m WHERE m.`config_value` = 'dashboard');
