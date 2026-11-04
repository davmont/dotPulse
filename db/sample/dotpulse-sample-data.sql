SET @dp_q = IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'dotp_projects') > 0, 'SET @dp_n = (SELECT COUNT(*) FROM `dotp_projects`) + (SELECT COUNT(*) FROM `dotp_companies`) + (SELECT COUNT(*) FROM `dotp_users`) - 1', 'SET @dp_n = 0');
PREPARE dp_s FROM @dp_q;
EXECUTE dp_s;
SET @dp_q = IF(@dp_n > 0, 'SELECT 1 FROM `This database already has data: sample data not loaded`', 'DO 0');
PREPARE dp_s FROM @dp_q;
EXECUTE dp_s;
DEALLOCATE PREPARE dp_s;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_api_tokens` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_api_tokens` WRITE;
DELETE FROM `dotp_api_tokens`;
/*!40000 ALTER TABLE `dotp_api_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_api_tokens` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_audit_log` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_date` datetime NOT NULL,
  `audit_user` int(11) NOT NULL DEFAULT 0,
  `audit_username` varchar(255) NOT NULL DEFAULT '',
  `audit_ip` varchar(45) NOT NULL DEFAULT '',
  `audit_event` varchar(40) NOT NULL DEFAULT '',
  `audit_object` varchar(40) NOT NULL DEFAULT '',
  `audit_object_id` int(11) NOT NULL DEFAULT 0,
  `audit_details` varchar(1000) NOT NULL DEFAULT '',
  PRIMARY KEY (`audit_id`),
  KEY `idx_audit_date` (`audit_date`),
  KEY `idx_audit_event` (`audit_event`),
  KEY `idx_audit_user` (`audit_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_audit_log` WRITE;
DELETE FROM `dotp_audit_log`;
/*!40000 ALTER TABLE `dotp_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_audit_log` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_billingcode` (
  `billingcode_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `billingcode_name` varchar(25) NOT NULL DEFAULT '',
  `billingcode_value` float NOT NULL DEFAULT 0,
  `billingcode_desc` varchar(255) NOT NULL DEFAULT '',
  `billingcode_status` int(1) NOT NULL DEFAULT 0,
  `company_id` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`billingcode_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_billingcode` WRITE;
DELETE FROM `dotp_billingcode`;
/*!40000 ALTER TABLE `dotp_billingcode` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_billingcode` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_budget` (
  `budget_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL DEFAULT 0,
  `Tax` decimal(4,2) NOT NULL DEFAULT 0.00,
  `display_tax` tinyint(1) NOT NULL DEFAULT 0,
  `only_financial` tinyint(1) NOT NULL DEFAULT 0,
  `equipment_investment` decimal(15,2) DEFAULT 0.00,
  `intangible_investment` decimal(15,2) DEFAULT 0.00,
  `service_investment` decimal(15,2) DEFAULT 0.00,
  `equipment_operation` decimal(15,2) DEFAULT 0.00,
  `intangible_operation` decimal(15,2) DEFAULT 0.00,
  `service_operation` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`budget_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_budget` WRITE;
DELETE FROM `dotp_budget`;
/*!40000 ALTER TABLE `dotp_budget` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_budget` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_companies` (
  `company_id` int(10) NOT NULL AUTO_INCREMENT,
  `company_module` int(10) NOT NULL DEFAULT 0,
  `company_name` varchar(100) DEFAULT '',
  `company_phone1` varchar(30) DEFAULT '',
  `company_phone2` varchar(30) DEFAULT '',
  `company_fax` varchar(30) DEFAULT '',
  `company_address1` varchar(50) DEFAULT '',
  `company_address2` varchar(50) DEFAULT '',
  `company_city` varchar(30) DEFAULT '',
  `company_state` varchar(30) DEFAULT '',
  `company_zip` varchar(11) DEFAULT '',
  `company_primary_url` varchar(255) DEFAULT '',
  `company_owner` int(11) NOT NULL DEFAULT 0,
  `company_description` text DEFAULT NULL,
  `company_type` int(3) NOT NULL DEFAULT 0,
  `company_email` varchar(255) DEFAULT NULL,
  `company_custom` longtext DEFAULT NULL,
  PRIMARY KEY (`company_id`),
  KEY `idx_cpy1` (`company_owner`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_companies` WRITE;
DELETE FROM `dotp_companies`;
/*!40000 ALTER TABLE `dotp_companies` DISABLE KEYS */;
INSERT INTO `dotp_companies` VALUES
(1,0,'Brightline Consulting','+351 21 000 1000','','','','','Lisbon','','','https://brightline.example',1,'Management and technology consultancy: the firm whose work this sample shows.',6,'info@brightline.example',NULL),
(2,0,'ACME Corporation','+1 602 555 0100','','','','','Phoenix','','','https://acme.example',1,'Manufacturer of everything, with three warehouses and a growing online shop.',1,'info@acme.example',NULL),
(3,0,'Dundler Mifflin Paper Company','+1 570 555 0150','','','','','Scranton','','','https://dundler.example',1,'Regional paper distributor. Brightline works with its Scranton branch.',1,'info@dundler.example',NULL),
(4,0,'Harbor & Vine Logistics','+351 22 000 2000','','','','','Porto','','','https://harborvine.example',1,'Last-mile delivery for grocers and wine merchants.',1,'info@harborvine.example',NULL),
(5,0,'Quill City Library Network','+44 20 7946 0300','','','','','Quill City','','','https://quill-libraries.example',1,'Public library network of twelve branches.',5,'info@quill-libraries.example',NULL);
/*!40000 ALTER TABLE `dotp_companies` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_config` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT,
  `config_name` varchar(255) NOT NULL DEFAULT '',
  `config_value` varchar(255) NOT NULL DEFAULT '',
  `config_group` varchar(255) NOT NULL DEFAULT '',
  `config_type` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `config_name` (`config_name`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_config` WRITE;
DELETE FROM `dotp_config`;
/*!40000 ALTER TABLE `dotp_config` DISABLE KEYS */;
INSERT INTO `dotp_config` VALUES
(1,'host_locale','en','','text'),
(2,'check_overallocation','false','','checkbox'),
(3,'currency_symbol','$','','text'),
(4,'host_style','default','','text'),
(5,'company_name','My Company','','text'),
(6,'page_title','dotPulse','','text'),
(7,'site_domain','example.com','','text'),
(8,'email_prefix','[dotPulse]','','text'),
(9,'admin_username','admin','','text'),
(10,'username_min_len','4','','text'),
(11,'password_min_len','4','','text'),
(12,'enable_gantt_charts','true','','checkbox'),
(13,'log_changes','false','','checkbox'),
(14,'check_task_dates','true','','checkbox'),
(15,'check_task_empty_dynamic','false','','checkbox'),
(16,'locale_warn','false','','checkbox'),
(17,'locale_alert','^','','text'),
(18,'daily_working_hours','8.0','','text'),
(19,'display_debug','false','','checkbox'),
(20,'link_tickets_kludge','false','','checkbox'),
(21,'show_all_task_assignees','false','','checkbox'),
(22,'direct_edit_assignment','false','','checkbox'),
(23,'restrict_color_selection','false','','checkbox'),
(24,'cal_day_view_show_minical','true','','checkbox'),
(25,'cal_day_start','8','','text'),
(26,'cal_day_end','17','','text'),
(27,'cal_day_increment','15','','text'),
(28,'cal_working_days','1,2,3,4,5','','text'),
(29,'restrict_task_time_editing','false','','checkbox'),
(30,'default_view_m','dashboard','','text'),
(31,'default_view_a','index','','text'),
(32,'default_view_tab','0','','text'),
(33,'index_max_file_size','-1','','text'),
(34,'session_handling','app','session','select'),
(35,'session_idle_time','2d','session','text'),
(36,'session_max_lifetime','1m','session','text'),
(37,'debug','1','','text'),
(38,'parser_default','/usr/bin/strings','','text'),
(39,'parser_application/msword','/usr/bin/strings','','text'),
(40,'parser_text/html','/usr/bin/strings','','text'),
(41,'parser_application/pdf','/usr/bin/pdftotext','','text'),
(42,'files_ci_preserve_attr','true','','checkbox'),
(43,'files_show_versions_edit','false','','checkbox'),
(44,'auth_method','sql','auth','select'),
(45,'ldap_host','localhost','ldap','text'),
(46,'ldap_port','389','ldap','text'),
(47,'ldap_version','3','ldap','text'),
(48,'ldap_base_dn','dc=saki,dc=com,dc=au','ldap','text'),
(49,'ldap_user_filter','(uid=%USERNAME%)','ldap','text'),
(50,'postnuke_allow_login','true','auth','checkbox'),
(51,'reset_memory_limit','32M','','text'),
(52,'mail_transport','php','mail','select'),
(53,'mail_host','localhost','mail','text'),
(54,'mail_port','25','mail','text'),
(55,'mail_auth','false','mail','checkbox'),
(56,'mail_user','','mail','text'),
(57,'mail_pass','','mail','password'),
(58,'mail_defer','false','mail','checkbox'),
(59,'mail_timeout','30','mail','text'),
(60,'mail_from','','mail','text'),
(61,'api_enabled','false','api','checkbox'),
(62,'audit_log_days','365','audit','text'),
(63,'session_gc_scan_queue','false','session','checkbox'),
(64,'task_reminder_control','false','task_reminder','checkbox'),
(65,'task_reminder_days_before','1','task_reminder','text'),
(66,'task_reminder_repeat','100','task_reminder','text'),
(67,'gacl_cache','false','gacl','checkbox'),
(68,'gacl_expire','true','gacl','checkbox'),
(69,'gacl_cache_dir','/tmp','gacl','text'),
(70,'gacl_timeout','600','gacl','text'),
(71,'mail_smtp_tls','false','mail','checkbox'),
(72,'ldap_search_user','Manager','ldap','text'),
(73,'ldap_search_pass','secret','ldap','password'),
(74,'ldap_allow_login','true','ldap','checkbox'),
(75,'user_contact_inactivate','true','auth','checkbox'),
(76,'user_contact_activate','false','auth','checkbox');
/*!40000 ALTER TABLE `dotp_config` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_config_list` (
  `config_list_id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL DEFAULT 0,
  `config_list_name` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`config_list_id`),
  KEY `config_id` (`config_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_config_list` WRITE;
DELETE FROM `dotp_config_list`;
/*!40000 ALTER TABLE `dotp_config_list` DISABLE KEYS */;
INSERT INTO `dotp_config_list` VALUES
(1,44,'sql'),
(2,44,'ldap'),
(3,44,'pn'),
(4,34,'app'),
(5,34,'php'),
(6,52,'php'),
(7,52,'smtp');
/*!40000 ALTER TABLE `dotp_config_list` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_contacts` (
  `contact_id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_first_name` varchar(30) DEFAULT NULL,
  `contact_last_name` varchar(30) DEFAULT NULL,
  `contact_order_by` varchar(30) NOT NULL DEFAULT '',
  `contact_title` varchar(50) DEFAULT NULL,
  `contact_birthday` date DEFAULT NULL,
  `contact_job` varchar(255) DEFAULT NULL,
  `contact_company` varchar(100) NOT NULL DEFAULT '',
  `contact_department` tinytext DEFAULT NULL,
  `contact_type` varchar(20) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_email2` varchar(255) DEFAULT NULL,
  `contact_url` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `contact_phone2` varchar(30) DEFAULT NULL,
  `contact_fax` varchar(30) DEFAULT NULL,
  `contact_mobile` varchar(30) DEFAULT NULL,
  `contact_address1` varchar(60) DEFAULT NULL,
  `contact_address2` varchar(60) DEFAULT NULL,
  `contact_city` varchar(30) DEFAULT NULL,
  `contact_state` varchar(30) DEFAULT NULL,
  `contact_zip` varchar(11) DEFAULT NULL,
  `contact_country` varchar(30) DEFAULT NULL,
  `contact_jabber` varchar(255) DEFAULT NULL,
  `contact_icq` varchar(20) DEFAULT NULL,
  `contact_msn` varchar(255) DEFAULT NULL,
  `contact_yahoo` varchar(255) DEFAULT NULL,
  `contact_aol` varchar(30) DEFAULT NULL,
  `contact_notes` text DEFAULT NULL,
  `contact_project` int(11) NOT NULL DEFAULT 0,
  `contact_icon` varchar(20) DEFAULT 'obj/contact',
  `contact_owner` int(10) unsigned DEFAULT 0,
  `contact_private` tinyint(3) unsigned DEFAULT 0,
  PRIMARY KEY (`contact_id`),
  KEY `idx_oby` (`contact_order_by`),
  KEY `idx_co` (`contact_company`),
  KEY `idx_prp` (`contact_project`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_contacts` WRITE;
DELETE FROM `dotp_contacts`;
/*!40000 ALTER TABLE `dotp_contacts` DISABLE KEYS */;
INSERT INTO `dotp_contacts` VALUES
(1,'Alex','Rivera','Rivera, Alex',NULL,NULL,'Managing Partner','1','2',NULL,'alex@brightline.example',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',0,0),
(2,'Priya','Nair','Nair, Priya',NULL,NULL,'Project Manager','1','1',NULL,'priya@brightline.example',NULL,NULL,'+351 21 000 1002',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0),
(3,'Marco','Bianchi','Bianchi, Marco',NULL,NULL,'Solutions Architect','1','1',NULL,'marco@brightline.example',NULL,NULL,'+351 21 000 1003',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0),
(4,'Hannah','Okafor','Okafor, Hannah',NULL,NULL,'Business Analyst','1','2',NULL,'hannah@brightline.example',NULL,NULL,'+351 21 000 1004',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0),
(5,'Tomas','Ruiz','Ruiz, Tomas',NULL,NULL,'Developer','1','1',NULL,'tomas@brightline.example',NULL,NULL,'+351 21 000 1005',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0),
(6,'Lena','Fischer','Fischer, Lena',NULL,NULL,'UX Designer','1','1',NULL,'lena@brightline.example',NULL,NULL,'+351 21 000 1006',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0),
(7,'Dana','Whitfield','Whitfield, Dana',NULL,NULL,'Operations Director','2',NULL,NULL,'dana.whitfield@acme.example',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0),
(8,'Raj','Patel','Patel, Raj',NULL,NULL,'IT Manager','2',NULL,NULL,'raj.patel@acme.example',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0),
(9,'Gloria','Benton','Benton, Gloria',NULL,NULL,'Branch Manager','3',NULL,NULL,'gloria.benton@dundler.example',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0),
(10,'Owen','Price','Price, Owen',NULL,NULL,'Sales Operations Lead','3',NULL,NULL,'owen.price@dundler.example',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0),
(11,'Sofia','Lindqvist','Lindqvist, Sofia',NULL,NULL,'Head of Fleet','4',NULL,NULL,'sofia.lindqvist@harborvine.example',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0),
(12,'Martin','Abara','Abara, Martin',NULL,NULL,'Digital Services Librarian','5',NULL,NULL,'martin.abara@quill-libraries.example',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'obj/contact',1,0);
/*!40000 ALTER TABLE `dotp_contacts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_custom_fields_lists` (
  `field_id` int(11) DEFAULT NULL,
  `list_option_id` int(11) DEFAULT NULL,
  `list_value` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_custom_fields_lists` WRITE;
DELETE FROM `dotp_custom_fields_lists`;
/*!40000 ALTER TABLE `dotp_custom_fields_lists` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_custom_fields_lists` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_custom_fields_struct` (
  `field_id` int(11) NOT NULL,
  `field_module` varchar(30) DEFAULT NULL,
  `field_page` varchar(30) DEFAULT NULL,
  `field_htmltype` varchar(20) DEFAULT NULL,
  `field_datatype` varchar(20) DEFAULT NULL,
  `field_order` int(11) DEFAULT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `field_extratags` varchar(250) DEFAULT NULL,
  `field_description` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_custom_fields_struct` WRITE;
DELETE FROM `dotp_custom_fields_struct`;
/*!40000 ALTER TABLE `dotp_custom_fields_struct` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_custom_fields_struct` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_custom_fields_values` (
  `value_id` int(11) DEFAULT NULL,
  `value_module` varchar(30) DEFAULT NULL,
  `value_object_id` int(11) DEFAULT NULL,
  `value_field_id` int(11) DEFAULT NULL,
  `value_charvalue` varchar(250) DEFAULT NULL,
  `value_intvalue` int(11) DEFAULT NULL,
  KEY `idx_cfv_id` (`value_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_custom_fields_values` WRITE;
DELETE FROM `dotp_custom_fields_values`;
/*!40000 ALTER TABLE `dotp_custom_fields_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_custom_fields_values` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_departments` (
  `dept_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dept_parent` int(10) unsigned NOT NULL DEFAULT 0,
  `dept_company` int(10) unsigned NOT NULL DEFAULT 0,
  `dept_name` tinytext NOT NULL,
  `dept_phone` varchar(30) DEFAULT NULL,
  `dept_fax` varchar(30) DEFAULT NULL,
  `dept_address1` varchar(30) DEFAULT NULL,
  `dept_address2` varchar(30) DEFAULT NULL,
  `dept_city` varchar(30) DEFAULT NULL,
  `dept_state` varchar(30) DEFAULT NULL,
  `dept_zip` varchar(11) DEFAULT NULL,
  `dept_url` varchar(25) DEFAULT NULL,
  `dept_desc` text DEFAULT NULL,
  `dept_owner` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`dept_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Department heirarchy under a company';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_departments` WRITE;
DELETE FROM `dotp_departments`;
/*!40000 ALTER TABLE `dotp_departments` DISABLE KEYS */;
INSERT INTO `dotp_departments` VALUES
(1,0,1,'Delivery',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),
(2,0,1,'Advisory',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),
(3,0,2,'Operations',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),
(4,0,2,'IT',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1),
(5,0,3,'Scranton Branch',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `dotp_departments` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_dotpermissions` (
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `user_id` varchar(80) NOT NULL DEFAULT '',
  `section` varchar(80) NOT NULL DEFAULT '',
  `axo` varchar(80) NOT NULL DEFAULT '',
  `permission` varchar(80) NOT NULL DEFAULT '',
  `allow` int(11) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 0,
  `enabled` int(11) NOT NULL DEFAULT 0,
  KEY `user_id` (`user_id`,`section`,`permission`,`axo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_dotpermissions` WRITE;
DELETE FROM `dotp_dotpermissions`;
/*!40000 ALTER TABLE `dotp_dotpermissions` DISABLE KEYS */;
INSERT INTO `dotp_dotpermissions` VALUES
(3,'1','sys','acl','access',1,3,1),
(7,'2','app','users','access',1,3,1),
(7,'3','app','users','access',1,3,1),
(7,'4','app','users','access',1,3,1),
(7,'5','app','users','access',1,3,1),
(7,'6','app','users','access',1,3,1),
(7,'2','app','users','view',1,3,1),
(7,'3','app','users','view',1,3,1),
(7,'4','app','users','view',1,3,1),
(7,'5','app','users','view',1,3,1),
(7,'6','app','users','view',1,3,1),
(2,'1','app','admin','access',1,4,1),
(2,'1','app','calendar','access',1,4,1),
(2,'1','app','events','access',1,4,1),
(2,'1','app','companies','access',1,4,1),
(2,'1','app','contacts','access',1,4,1),
(2,'1','app','departments','access',1,4,1),
(2,'1','app','files','access',1,4,1),
(2,'1','app','file_folders','access',1,4,1),
(2,'1','app','forums','access',1,4,1),
(2,'1','app','help','access',1,4,1),
(2,'1','app','projects','access',1,4,1),
(2,'1','app','system','access',1,4,1),
(2,'1','app','tasks','access',1,4,1),
(2,'1','app','task_log','access',1,4,1),
(2,'1','app','ticketsmith','access',1,4,1),
(2,'1','app','public','access',1,4,1),
(2,'1','app','roles','access',1,4,1),
(2,'1','app','users','access',1,4,1),
(2,'1','app','dashboard','access',1,4,1),
(2,'1','app','admin','add',1,4,1),
(2,'1','app','calendar','add',1,4,1),
(2,'1','app','events','add',1,4,1),
(2,'1','app','companies','add',1,4,1),
(2,'1','app','contacts','add',1,4,1),
(2,'1','app','departments','add',1,4,1),
(2,'1','app','files','add',1,4,1),
(2,'1','app','file_folders','add',1,4,1),
(2,'1','app','forums','add',1,4,1),
(2,'1','app','help','add',1,4,1),
(2,'1','app','projects','add',1,4,1),
(2,'1','app','system','add',1,4,1),
(2,'1','app','tasks','add',1,4,1),
(2,'1','app','task_log','add',1,4,1),
(2,'1','app','ticketsmith','add',1,4,1),
(2,'1','app','public','add',1,4,1),
(2,'1','app','roles','add',1,4,1),
(2,'1','app','users','add',1,4,1),
(2,'1','app','dashboard','add',1,4,1),
(2,'1','app','admin','delete',1,4,1),
(2,'1','app','calendar','delete',1,4,1),
(2,'1','app','events','delete',1,4,1),
(2,'1','app','companies','delete',1,4,1),
(2,'1','app','contacts','delete',1,4,1),
(2,'1','app','departments','delete',1,4,1),
(2,'1','app','files','delete',1,4,1),
(2,'1','app','file_folders','delete',1,4,1),
(2,'1','app','forums','delete',1,4,1),
(2,'1','app','help','delete',1,4,1),
(2,'1','app','projects','delete',1,4,1),
(2,'1','app','system','delete',1,4,1),
(2,'1','app','tasks','delete',1,4,1),
(2,'1','app','task_log','delete',1,4,1),
(2,'1','app','ticketsmith','delete',1,4,1),
(2,'1','app','public','delete',1,4,1),
(2,'1','app','roles','delete',1,4,1),
(2,'1','app','users','delete',1,4,1),
(2,'1','app','dashboard','delete',1,4,1),
(2,'1','app','admin','edit',1,4,1),
(2,'1','app','calendar','edit',1,4,1),
(2,'1','app','events','edit',1,4,1),
(2,'1','app','companies','edit',1,4,1),
(2,'1','app','contacts','edit',1,4,1),
(2,'1','app','departments','edit',1,4,1),
(2,'1','app','files','edit',1,4,1),
(2,'1','app','file_folders','edit',1,4,1),
(2,'1','app','forums','edit',1,4,1),
(2,'1','app','help','edit',1,4,1),
(2,'1','app','projects','edit',1,4,1),
(2,'1','app','system','edit',1,4,1),
(2,'1','app','tasks','edit',1,4,1),
(2,'1','app','task_log','edit',1,4,1),
(2,'1','app','ticketsmith','edit',1,4,1),
(2,'1','app','public','edit',1,4,1),
(2,'1','app','roles','edit',1,4,1),
(2,'1','app','users','edit',1,4,1),
(2,'1','app','dashboard','edit',1,4,1),
(2,'1','app','admin','view',1,4,1),
(2,'1','app','calendar','view',1,4,1),
(2,'1','app','events','view',1,4,1),
(2,'1','app','companies','view',1,4,1),
(2,'1','app','contacts','view',1,4,1),
(2,'1','app','departments','view',1,4,1),
(2,'1','app','files','view',1,4,1),
(2,'1','app','file_folders','view',1,4,1),
(2,'1','app','forums','view',1,4,1),
(2,'1','app','help','view',1,4,1),
(2,'1','app','projects','view',1,4,1),
(2,'1','app','system','view',1,4,1),
(2,'1','app','tasks','view',1,4,1),
(2,'1','app','task_log','view',1,4,1),
(2,'1','app','ticketsmith','view',1,4,1),
(2,'1','app','public','view',1,4,1),
(2,'1','app','roles','view',1,4,1),
(2,'1','app','users','view',1,4,1),
(2,'1','app','dashboard','view',1,4,1),
(6,'2','app','calendar','access',1,4,1),
(6,'2','app','events','access',1,4,1),
(6,'2','app','companies','access',1,4,1),
(6,'2','app','contacts','access',1,4,1),
(6,'2','app','departments','access',1,4,1),
(6,'2','app','files','access',1,4,1),
(6,'2','app','file_folders','access',1,4,1),
(6,'2','app','forums','access',1,4,1),
(6,'2','app','help','access',1,4,1),
(6,'2','app','projects','access',1,4,1),
(6,'2','app','tasks','access',1,4,1),
(6,'2','app','task_log','access',1,4,1),
(6,'2','app','ticketsmith','access',1,4,1),
(6,'2','app','public','access',1,4,1),
(6,'2','app','dashboard','access',1,4,1),
(6,'3','app','calendar','access',1,4,1),
(6,'3','app','events','access',1,4,1),
(6,'3','app','companies','access',1,4,1),
(6,'3','app','contacts','access',1,4,1),
(6,'3','app','departments','access',1,4,1),
(6,'3','app','files','access',1,4,1),
(6,'3','app','file_folders','access',1,4,1),
(6,'3','app','forums','access',1,4,1),
(6,'3','app','help','access',1,4,1),
(6,'3','app','projects','access',1,4,1),
(6,'3','app','tasks','access',1,4,1),
(6,'3','app','task_log','access',1,4,1),
(6,'3','app','ticketsmith','access',1,4,1),
(6,'3','app','public','access',1,4,1),
(6,'3','app','dashboard','access',1,4,1),
(6,'4','app','calendar','access',1,4,1),
(6,'4','app','events','access',1,4,1),
(6,'4','app','companies','access',1,4,1),
(6,'4','app','contacts','access',1,4,1),
(6,'4','app','departments','access',1,4,1),
(6,'4','app','files','access',1,4,1),
(6,'4','app','file_folders','access',1,4,1),
(6,'4','app','forums','access',1,4,1),
(6,'4','app','help','access',1,4,1),
(6,'4','app','projects','access',1,4,1),
(6,'4','app','tasks','access',1,4,1),
(6,'4','app','task_log','access',1,4,1),
(6,'4','app','ticketsmith','access',1,4,1),
(6,'4','app','public','access',1,4,1),
(6,'4','app','dashboard','access',1,4,1),
(6,'5','app','calendar','access',1,4,1),
(6,'5','app','events','access',1,4,1),
(6,'5','app','companies','access',1,4,1),
(6,'5','app','contacts','access',1,4,1),
(6,'5','app','departments','access',1,4,1),
(6,'5','app','files','access',1,4,1),
(6,'5','app','file_folders','access',1,4,1),
(6,'5','app','forums','access',1,4,1),
(6,'5','app','help','access',1,4,1),
(6,'5','app','projects','access',1,4,1),
(6,'5','app','tasks','access',1,4,1),
(6,'5','app','task_log','access',1,4,1),
(6,'5','app','ticketsmith','access',1,4,1),
(6,'5','app','public','access',1,4,1),
(6,'5','app','dashboard','access',1,4,1),
(6,'6','app','calendar','access',1,4,1),
(6,'6','app','events','access',1,4,1),
(6,'6','app','companies','access',1,4,1),
(6,'6','app','contacts','access',1,4,1),
(6,'6','app','departments','access',1,4,1),
(6,'6','app','files','access',1,4,1),
(6,'6','app','file_folders','access',1,4,1),
(6,'6','app','forums','access',1,4,1),
(6,'6','app','help','access',1,4,1),
(6,'6','app','projects','access',1,4,1),
(6,'6','app','tasks','access',1,4,1),
(6,'6','app','task_log','access',1,4,1),
(6,'6','app','ticketsmith','access',1,4,1),
(6,'6','app','public','access',1,4,1),
(6,'6','app','dashboard','access',1,4,1),
(6,'2','app','calendar','add',1,4,1),
(6,'2','app','events','add',1,4,1),
(6,'2','app','companies','add',1,4,1),
(6,'2','app','contacts','add',1,4,1),
(6,'2','app','departments','add',1,4,1),
(6,'2','app','files','add',1,4,1),
(6,'2','app','file_folders','add',1,4,1),
(6,'2','app','forums','add',1,4,1),
(6,'2','app','help','add',1,4,1),
(6,'2','app','projects','add',1,4,1),
(6,'2','app','tasks','add',1,4,1),
(6,'2','app','task_log','add',1,4,1),
(6,'2','app','ticketsmith','add',1,4,1),
(6,'2','app','public','add',1,4,1),
(6,'2','app','dashboard','add',1,4,1),
(6,'3','app','calendar','add',1,4,1),
(6,'3','app','events','add',1,4,1),
(6,'3','app','companies','add',1,4,1),
(6,'3','app','contacts','add',1,4,1),
(6,'3','app','departments','add',1,4,1),
(6,'3','app','files','add',1,4,1),
(6,'3','app','file_folders','add',1,4,1),
(6,'3','app','forums','add',1,4,1),
(6,'3','app','help','add',1,4,1),
(6,'3','app','projects','add',1,4,1),
(6,'3','app','tasks','add',1,4,1),
(6,'3','app','task_log','add',1,4,1),
(6,'3','app','ticketsmith','add',1,4,1),
(6,'3','app','public','add',1,4,1),
(6,'3','app','dashboard','add',1,4,1),
(6,'4','app','calendar','add',1,4,1),
(6,'4','app','events','add',1,4,1),
(6,'4','app','companies','add',1,4,1),
(6,'4','app','contacts','add',1,4,1),
(6,'4','app','departments','add',1,4,1),
(6,'4','app','files','add',1,4,1),
(6,'4','app','file_folders','add',1,4,1),
(6,'4','app','forums','add',1,4,1),
(6,'4','app','help','add',1,4,1),
(6,'4','app','projects','add',1,4,1),
(6,'4','app','tasks','add',1,4,1),
(6,'4','app','task_log','add',1,4,1),
(6,'4','app','ticketsmith','add',1,4,1),
(6,'4','app','public','add',1,4,1),
(6,'4','app','dashboard','add',1,4,1),
(6,'5','app','calendar','add',1,4,1),
(6,'5','app','events','add',1,4,1),
(6,'5','app','companies','add',1,4,1),
(6,'5','app','contacts','add',1,4,1),
(6,'5','app','departments','add',1,4,1),
(6,'5','app','files','add',1,4,1),
(6,'5','app','file_folders','add',1,4,1),
(6,'5','app','forums','add',1,4,1),
(6,'5','app','help','add',1,4,1),
(6,'5','app','projects','add',1,4,1),
(6,'5','app','tasks','add',1,4,1),
(6,'5','app','task_log','add',1,4,1),
(6,'5','app','ticketsmith','add',1,4,1),
(6,'5','app','public','add',1,4,1),
(6,'5','app','dashboard','add',1,4,1),
(6,'6','app','calendar','add',1,4,1),
(6,'6','app','events','add',1,4,1),
(6,'6','app','companies','add',1,4,1),
(6,'6','app','contacts','add',1,4,1),
(6,'6','app','departments','add',1,4,1),
(6,'6','app','files','add',1,4,1),
(6,'6','app','file_folders','add',1,4,1),
(6,'6','app','forums','add',1,4,1),
(6,'6','app','help','add',1,4,1),
(6,'6','app','projects','add',1,4,1),
(6,'6','app','tasks','add',1,4,1),
(6,'6','app','task_log','add',1,4,1),
(6,'6','app','ticketsmith','add',1,4,1),
(6,'6','app','public','add',1,4,1),
(6,'6','app','dashboard','add',1,4,1),
(6,'2','app','calendar','delete',1,4,1),
(6,'2','app','events','delete',1,4,1),
(6,'2','app','companies','delete',1,4,1),
(6,'2','app','contacts','delete',1,4,1),
(6,'2','app','departments','delete',1,4,1),
(6,'2','app','files','delete',1,4,1),
(6,'2','app','file_folders','delete',1,4,1),
(6,'2','app','forums','delete',1,4,1),
(6,'2','app','help','delete',1,4,1),
(6,'2','app','projects','delete',1,4,1),
(6,'2','app','tasks','delete',1,4,1),
(6,'2','app','task_log','delete',1,4,1),
(6,'2','app','ticketsmith','delete',1,4,1),
(6,'2','app','public','delete',1,4,1),
(6,'2','app','dashboard','delete',1,4,1),
(6,'3','app','calendar','delete',1,4,1),
(6,'3','app','events','delete',1,4,1),
(6,'3','app','companies','delete',1,4,1),
(6,'3','app','contacts','delete',1,4,1),
(6,'3','app','departments','delete',1,4,1),
(6,'3','app','files','delete',1,4,1),
(6,'3','app','file_folders','delete',1,4,1),
(6,'3','app','forums','delete',1,4,1),
(6,'3','app','help','delete',1,4,1),
(6,'3','app','projects','delete',1,4,1),
(6,'3','app','tasks','delete',1,4,1),
(6,'3','app','task_log','delete',1,4,1),
(6,'3','app','ticketsmith','delete',1,4,1),
(6,'3','app','public','delete',1,4,1),
(6,'3','app','dashboard','delete',1,4,1),
(6,'4','app','calendar','delete',1,4,1),
(6,'4','app','events','delete',1,4,1),
(6,'4','app','companies','delete',1,4,1),
(6,'4','app','contacts','delete',1,4,1),
(6,'4','app','departments','delete',1,4,1),
(6,'4','app','files','delete',1,4,1),
(6,'4','app','file_folders','delete',1,4,1),
(6,'4','app','forums','delete',1,4,1),
(6,'4','app','help','delete',1,4,1),
(6,'4','app','projects','delete',1,4,1),
(6,'4','app','tasks','delete',1,4,1),
(6,'4','app','task_log','delete',1,4,1),
(6,'4','app','ticketsmith','delete',1,4,1),
(6,'4','app','public','delete',1,4,1),
(6,'4','app','dashboard','delete',1,4,1),
(6,'5','app','calendar','delete',1,4,1),
(6,'5','app','events','delete',1,4,1),
(6,'5','app','companies','delete',1,4,1),
(6,'5','app','contacts','delete',1,4,1),
(6,'5','app','departments','delete',1,4,1),
(6,'5','app','files','delete',1,4,1),
(6,'5','app','file_folders','delete',1,4,1),
(6,'5','app','forums','delete',1,4,1),
(6,'5','app','help','delete',1,4,1),
(6,'5','app','projects','delete',1,4,1),
(6,'5','app','tasks','delete',1,4,1),
(6,'5','app','task_log','delete',1,4,1),
(6,'5','app','ticketsmith','delete',1,4,1),
(6,'5','app','public','delete',1,4,1),
(6,'5','app','dashboard','delete',1,4,1),
(6,'6','app','calendar','delete',1,4,1),
(6,'6','app','events','delete',1,4,1),
(6,'6','app','companies','delete',1,4,1),
(6,'6','app','contacts','delete',1,4,1),
(6,'6','app','departments','delete',1,4,1),
(6,'6','app','files','delete',1,4,1),
(6,'6','app','file_folders','delete',1,4,1),
(6,'6','app','forums','delete',1,4,1),
(6,'6','app','help','delete',1,4,1),
(6,'6','app','projects','delete',1,4,1),
(6,'6','app','tasks','delete',1,4,1),
(6,'6','app','task_log','delete',1,4,1),
(6,'6','app','ticketsmith','delete',1,4,1),
(6,'6','app','public','delete',1,4,1),
(6,'6','app','dashboard','delete',1,4,1),
(6,'2','app','calendar','edit',1,4,1),
(6,'2','app','events','edit',1,4,1),
(6,'2','app','companies','edit',1,4,1),
(6,'2','app','contacts','edit',1,4,1),
(6,'2','app','departments','edit',1,4,1),
(6,'2','app','files','edit',1,4,1),
(6,'2','app','file_folders','edit',1,4,1),
(6,'2','app','forums','edit',1,4,1),
(6,'2','app','help','edit',1,4,1),
(6,'2','app','projects','edit',1,4,1),
(6,'2','app','tasks','edit',1,4,1),
(6,'2','app','task_log','edit',1,4,1),
(6,'2','app','ticketsmith','edit',1,4,1),
(6,'2','app','public','edit',1,4,1),
(6,'2','app','dashboard','edit',1,4,1),
(6,'3','app','calendar','edit',1,4,1),
(6,'3','app','events','edit',1,4,1),
(6,'3','app','companies','edit',1,4,1),
(6,'3','app','contacts','edit',1,4,1),
(6,'3','app','departments','edit',1,4,1),
(6,'3','app','files','edit',1,4,1),
(6,'3','app','file_folders','edit',1,4,1),
(6,'3','app','forums','edit',1,4,1),
(6,'3','app','help','edit',1,4,1),
(6,'3','app','projects','edit',1,4,1),
(6,'3','app','tasks','edit',1,4,1),
(6,'3','app','task_log','edit',1,4,1),
(6,'3','app','ticketsmith','edit',1,4,1),
(6,'3','app','public','edit',1,4,1),
(6,'3','app','dashboard','edit',1,4,1),
(6,'4','app','calendar','edit',1,4,1),
(6,'4','app','events','edit',1,4,1),
(6,'4','app','companies','edit',1,4,1),
(6,'4','app','contacts','edit',1,4,1),
(6,'4','app','departments','edit',1,4,1),
(6,'4','app','files','edit',1,4,1),
(6,'4','app','file_folders','edit',1,4,1),
(6,'4','app','forums','edit',1,4,1),
(6,'4','app','help','edit',1,4,1),
(6,'4','app','projects','edit',1,4,1),
(6,'4','app','tasks','edit',1,4,1),
(6,'4','app','task_log','edit',1,4,1),
(6,'4','app','ticketsmith','edit',1,4,1),
(6,'4','app','public','edit',1,4,1),
(6,'4','app','dashboard','edit',1,4,1),
(6,'5','app','calendar','edit',1,4,1),
(6,'5','app','events','edit',1,4,1),
(6,'5','app','companies','edit',1,4,1),
(6,'5','app','contacts','edit',1,4,1),
(6,'5','app','departments','edit',1,4,1),
(6,'5','app','files','edit',1,4,1),
(6,'5','app','file_folders','edit',1,4,1),
(6,'5','app','forums','edit',1,4,1),
(6,'5','app','help','edit',1,4,1),
(6,'5','app','projects','edit',1,4,1),
(6,'5','app','tasks','edit',1,4,1),
(6,'5','app','task_log','edit',1,4,1),
(6,'5','app','ticketsmith','edit',1,4,1),
(6,'5','app','public','edit',1,4,1),
(6,'5','app','dashboard','edit',1,4,1),
(6,'6','app','calendar','edit',1,4,1),
(6,'6','app','events','edit',1,4,1),
(6,'6','app','companies','edit',1,4,1),
(6,'6','app','contacts','edit',1,4,1),
(6,'6','app','departments','edit',1,4,1),
(6,'6','app','files','edit',1,4,1),
(6,'6','app','file_folders','edit',1,4,1),
(6,'6','app','forums','edit',1,4,1),
(6,'6','app','help','edit',1,4,1),
(6,'6','app','projects','edit',1,4,1),
(6,'6','app','tasks','edit',1,4,1),
(6,'6','app','task_log','edit',1,4,1),
(6,'6','app','ticketsmith','edit',1,4,1),
(6,'6','app','public','edit',1,4,1),
(6,'6','app','dashboard','edit',1,4,1),
(6,'2','app','calendar','view',1,4,1),
(6,'2','app','events','view',1,4,1),
(6,'2','app','companies','view',1,4,1),
(6,'2','app','contacts','view',1,4,1),
(6,'2','app','departments','view',1,4,1),
(6,'2','app','files','view',1,4,1),
(6,'2','app','file_folders','view',1,4,1),
(6,'2','app','forums','view',1,4,1),
(6,'2','app','help','view',1,4,1),
(6,'2','app','projects','view',1,4,1),
(6,'2','app','tasks','view',1,4,1),
(6,'2','app','task_log','view',1,4,1),
(6,'2','app','ticketsmith','view',1,4,1),
(6,'2','app','public','view',1,4,1),
(6,'2','app','dashboard','view',1,4,1),
(6,'3','app','calendar','view',1,4,1),
(6,'3','app','events','view',1,4,1),
(6,'3','app','companies','view',1,4,1),
(6,'3','app','contacts','view',1,4,1),
(6,'3','app','departments','view',1,4,1),
(6,'3','app','files','view',1,4,1),
(6,'3','app','file_folders','view',1,4,1),
(6,'3','app','forums','view',1,4,1),
(6,'3','app','help','view',1,4,1),
(6,'3','app','projects','view',1,4,1),
(6,'3','app','tasks','view',1,4,1),
(6,'3','app','task_log','view',1,4,1),
(6,'3','app','ticketsmith','view',1,4,1),
(6,'3','app','public','view',1,4,1),
(6,'3','app','dashboard','view',1,4,1),
(6,'4','app','calendar','view',1,4,1),
(6,'4','app','events','view',1,4,1),
(6,'4','app','companies','view',1,4,1),
(6,'4','app','contacts','view',1,4,1),
(6,'4','app','departments','view',1,4,1),
(6,'4','app','files','view',1,4,1),
(6,'4','app','file_folders','view',1,4,1),
(6,'4','app','forums','view',1,4,1),
(6,'4','app','help','view',1,4,1),
(6,'4','app','projects','view',1,4,1),
(6,'4','app','tasks','view',1,4,1),
(6,'4','app','task_log','view',1,4,1),
(6,'4','app','ticketsmith','view',1,4,1),
(6,'4','app','public','view',1,4,1),
(6,'4','app','dashboard','view',1,4,1),
(6,'5','app','calendar','view',1,4,1),
(6,'5','app','events','view',1,4,1),
(6,'5','app','companies','view',1,4,1),
(6,'5','app','contacts','view',1,4,1),
(6,'5','app','departments','view',1,4,1),
(6,'5','app','files','view',1,4,1),
(6,'5','app','file_folders','view',1,4,1),
(6,'5','app','forums','view',1,4,1),
(6,'5','app','help','view',1,4,1),
(6,'5','app','projects','view',1,4,1),
(6,'5','app','tasks','view',1,4,1),
(6,'5','app','task_log','view',1,4,1),
(6,'5','app','ticketsmith','view',1,4,1),
(6,'5','app','public','view',1,4,1),
(6,'5','app','dashboard','view',1,4,1),
(6,'6','app','calendar','view',1,4,1),
(6,'6','app','events','view',1,4,1),
(6,'6','app','companies','view',1,4,1),
(6,'6','app','contacts','view',1,4,1),
(6,'6','app','departments','view',1,4,1),
(6,'6','app','files','view',1,4,1),
(6,'6','app','file_folders','view',1,4,1),
(6,'6','app','forums','view',1,4,1),
(6,'6','app','help','view',1,4,1),
(6,'6','app','projects','view',1,4,1),
(6,'6','app','tasks','view',1,4,1),
(6,'6','app','task_log','view',1,4,1),
(6,'6','app','ticketsmith','view',1,4,1),
(6,'6','app','public','view',1,4,1),
(6,'6','app','dashboard','view',1,4,1);
/*!40000 ALTER TABLE `dotp_dotpermissions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_dpversion` (
  `code_version` varchar(10) NOT NULL DEFAULT '',
  `db_version` int(11) NOT NULL DEFAULT 0,
  `last_db_update` date NOT NULL DEFAULT '0000-00-00',
  `last_code_update` date NOT NULL DEFAULT '0000-00-00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_dpversion` WRITE;
DELETE FROM `dotp_dpversion`;
/*!40000 ALTER TABLE `dotp_dpversion` DISABLE KEYS */;
INSERT INTO `dotp_dpversion` VALUES
('3.1.0',3,'2026-11-04','2012-09-06');
/*!40000 ALTER TABLE `dotp_dpversion` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_event_queue` (
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_start` int(11) NOT NULL DEFAULT 0,
  `queue_type` varchar(40) NOT NULL DEFAULT '',
  `queue_repeat_interval` int(11) NOT NULL DEFAULT 0,
  `queue_repeat_count` int(11) NOT NULL DEFAULT 0,
  `queue_data` longblob NOT NULL,
  `queue_callback` varchar(127) NOT NULL DEFAULT '',
  `queue_owner` int(11) NOT NULL DEFAULT 0,
  `queue_origin_id` int(11) NOT NULL DEFAULT 0,
  `queue_module` varchar(40) NOT NULL DEFAULT '',
  `queue_module_type` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`queue_id`),
  KEY `queue_start` (`queue_start`),
  KEY `queue_module` (`queue_module`),
  KEY `queue_type` (`queue_type`),
  KEY `queue_origin_id` (`queue_origin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_event_queue` WRITE;
DELETE FROM `dotp_event_queue`;
/*!40000 ALTER TABLE `dotp_event_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_event_queue` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_title` varchar(255) NOT NULL DEFAULT '',
  `event_start_date` datetime DEFAULT NULL,
  `event_end_date` datetime DEFAULT NULL,
  `event_parent` int(11) unsigned NOT NULL DEFAULT 0,
  `event_description` text DEFAULT NULL,
  `event_times_recuring` int(11) unsigned NOT NULL DEFAULT 0,
  `event_recurs` int(11) unsigned NOT NULL DEFAULT 0,
  `event_remind` int(10) unsigned NOT NULL DEFAULT 0,
  `event_icon` varchar(20) DEFAULT 'obj/event',
  `event_owner` int(11) DEFAULT 0,
  `event_project` int(11) DEFAULT 0,
  `event_private` tinyint(3) DEFAULT 0,
  `event_type` tinyint(3) DEFAULT 0,
  `event_cwd` tinyint(3) DEFAULT 0,
  `event_notify` tinyint(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`event_id`),
  KEY `id_esd` (`event_start_date`),
  KEY `id_eed` (`event_end_date`),
  KEY `id_evp` (`event_parent`),
  KEY `idx_ev1` (`event_owner`),
  KEY `idx_ev2` (`event_project`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_events` WRITE;
DELETE FROM `dotp_events`;
/*!40000 ALTER TABLE `dotp_events` DISABLE KEYS */;
INSERT INTO `dotp_events` VALUES
(1,'ACME weekly status','2026-10-21 10:00:00','2026-10-21 11:00:00',0,'Weekly status with Dana and Raj.',0,0,0,'obj/event',2,1,0,2,0,0),
(2,'ACME weekly status','2026-10-28 10:00:00','2026-10-28 11:00:00',0,'Weekly status with Dana and Raj.',0,0,0,'obj/event',2,1,0,2,0,0),
(3,'ACME weekly status','2026-11-04 10:00:00','2026-11-04 11:00:00',0,'Weekly status with Dana and Raj.',0,0,0,'obj/event',2,1,0,2,0,0),
(4,'ACME weekly status','2026-11-11 10:00:00','2026-11-11 11:00:00',0,'Weekly status with Dana and Raj.',0,0,0,'obj/event',2,1,0,2,0,0),
(5,'Pilot go-live readiness review','2026-11-09 14:00:00','2026-11-09 16:00:00',0,'Go / no-go for the Building 3 pilot.',0,0,0,'obj/event',2,1,0,2,0,0),
(6,'Dundler Mifflin data clean-up workshop','2026-11-02 13:00:00','2026-11-02 16:00:00',0,'Duplicate customers: agree merge rules with Owen.',0,0,0,'obj/event',4,4,0,2,0,0),
(7,'CRM recovery plan with Gloria','2026-11-05 11:00:00','2026-11-05 12:00:00',0,'Revised cut-over date and the extra clean-up work.',0,0,0,'obj/event',2,4,0,2,0,0),
(8,'Harbor & Vine depot visit','2026-11-05 09:00:00','2026-11-05 13:00:00',0,'Ride along with two drivers; collect delivery windows.',0,0,0,'obj/event',3,6,0,1,0,0),
(9,'Quill City kick-off','2026-11-09 10:00:00','2026-11-09 12:00:00',0,'Kick-off with Martin and the branch managers.',0,0,0,'obj/event',4,7,0,2,0,0),
(10,'Brightline monthly all-hands','2026-11-10 16:00:00','2026-11-10 17:00:00',0,'Pipeline, utilisation and lessons learned.',0,0,0,'obj/event',1,0,0,2,0,0);
/*!40000 ALTER TABLE `dotp_events` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_file_folders` (
  `file_folder_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_folder_parent` int(11) NOT NULL DEFAULT 0,
  `file_folder_name` varchar(255) NOT NULL DEFAULT '',
  `file_folder_description` text DEFAULT NULL,
  PRIMARY KEY (`file_folder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_file_folders` WRITE;
DELETE FROM `dotp_file_folders`;
/*!40000 ALTER TABLE `dotp_file_folders` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_file_folders` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_real_filename` varchar(255) NOT NULL DEFAULT '',
  `file_folder` int(11) NOT NULL DEFAULT 0,
  `file_project` int(11) NOT NULL DEFAULT 0,
  `file_task` int(11) NOT NULL DEFAULT 0,
  `file_name` varchar(255) NOT NULL DEFAULT '',
  `file_parent` int(11) DEFAULT 0,
  `file_description` text DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_owner` int(11) DEFAULT 0,
  `file_date` datetime DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `file_version` float NOT NULL DEFAULT 0,
  `file_icon` varchar(20) DEFAULT 'obj/',
  `file_category` int(11) DEFAULT 0,
  `file_checkout` varchar(255) NOT NULL DEFAULT '',
  `file_co_reason` text DEFAULT NULL,
  `file_version_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`file_id`),
  KEY `idx_file_task` (`file_task`),
  KEY `idx_file_project` (`file_project`),
  KEY `idx_file_parent` (`file_parent`),
  KEY `idx_file_vid` (`file_version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_files` WRITE;
DELETE FROM `dotp_files`;
/*!40000 ALTER TABLE `dotp_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_files` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_files_index` (
  `file_id` int(11) NOT NULL DEFAULT 0,
  `word` varchar(50) NOT NULL DEFAULT '',
  `word_placement` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`file_id`,`word`,`word_placement`),
  KEY `idx_fwrd` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_files_index` WRITE;
DELETE FROM `dotp_files_index`;
/*!40000 ALTER TABLE `dotp_files_index` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_files_index` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_forum_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `message_forum` int(11) NOT NULL DEFAULT 0,
  `message_parent` int(11) NOT NULL DEFAULT 0,
  `message_author` int(11) NOT NULL DEFAULT 0,
  `message_editor` int(11) NOT NULL DEFAULT 0,
  `message_title` varchar(255) NOT NULL DEFAULT '',
  `message_date` datetime DEFAULT '0000-00-00 00:00:00',
  `message_body` text DEFAULT NULL,
  `message_published` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`message_id`),
  KEY `idx_mparent` (`message_parent`),
  KEY `idx_mdate` (`message_date`),
  KEY `idx_mforum` (`message_forum`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_forum_messages` WRITE;
DELETE FROM `dotp_forum_messages`;
/*!40000 ALTER TABLE `dotp_forum_messages` DISABLE KEYS */;
INSERT INTO `dotp_forum_messages` VALUES
(1,1,-1,3,0,'Scanner firmware for Building 3','2026-10-27 11:00:00','The vendor ships firmware 4.2 next week; it fixes the double-scan issue we saw in testing. I suggest we wait for it before go-live.',1),
(2,1,1,5,0,'Re: Scanner firmware for Building 3','2026-10-28 11:00:00','Agreed. I can flash all 24 scanners in an afternoon once it arrives.',1),
(3,1,1,2,0,'Re: Scanner firmware for Building 3','2026-10-28 11:00:00','OK: go-live stays on the plan, firmware first. I told Raj.',1),
(4,2,-1,4,0,'Duplicate customers','2026-10-29 11:00:00','About 18% of customer records exist in both old systems with small differences (suite numbers, contact names). Proposed rule: keep the record with the latest order.',1),
(5,2,4,2,0,'Re: Duplicate customers','2026-11-03 11:00:00','Owen agreed in the workshop. This adds about a week; I am preparing a revised plan for Gloria.',1),
(6,3,-1,3,0,'Engine shortlist','2026-10-30 11:00:00','Two open-source routing engines handle time windows well. Comparing them on last month\'s deliveries this week.',1);
/*!40000 ALTER TABLE `dotp_forum_messages` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_forum_visits` (
  `visit_user` int(10) NOT NULL DEFAULT 0,
  `visit_forum` int(10) NOT NULL DEFAULT 0,
  `visit_message` int(10) NOT NULL DEFAULT 0,
  `visit_date` timestamp NULL DEFAULT NULL,
  KEY `idx_fv` (`visit_user`,`visit_forum`,`visit_message`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_forum_visits` WRITE;
DELETE FROM `dotp_forum_visits`;
/*!40000 ALTER TABLE `dotp_forum_visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_forum_visits` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_forum_watch` (
  `watch_user` int(10) unsigned NOT NULL DEFAULT 0,
  `watch_forum` int(10) unsigned DEFAULT NULL,
  `watch_topic` int(10) unsigned DEFAULT NULL,
  KEY `idx_fw1` (`watch_user`,`watch_forum`),
  KEY `idx_fw2` (`watch_user`,`watch_topic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Links users to the forums/messages they are watching';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_forum_watch` WRITE;
DELETE FROM `dotp_forum_watch`;
/*!40000 ALTER TABLE `dotp_forum_watch` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_forum_watch` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_forums` (
  `forum_id` int(11) NOT NULL AUTO_INCREMENT,
  `forum_project` int(11) NOT NULL DEFAULT 0,
  `forum_status` tinyint(4) NOT NULL DEFAULT -1,
  `forum_owner` int(11) NOT NULL DEFAULT 0,
  `forum_name` varchar(50) NOT NULL DEFAULT '',
  `forum_create_date` datetime DEFAULT '0000-00-00 00:00:00',
  `forum_last_date` datetime DEFAULT '0000-00-00 00:00:00',
  `forum_last_id` int(10) unsigned NOT NULL DEFAULT 0,
  `forum_message_count` int(11) NOT NULL DEFAULT 0,
  `forum_description` varchar(255) DEFAULT NULL,
  `forum_moderated` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`forum_id`),
  KEY `idx_fproject` (`forum_project`),
  KEY `idx_fowner` (`forum_owner`),
  KEY `forum_status` (`forum_status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_forums` WRITE;
DELETE FROM `dotp_forums`;
/*!40000 ALTER TABLE `dotp_forums` DISABLE KEYS */;
INSERT INTO `dotp_forums` VALUES
(1,1,1,2,'Warehouse automation','2026-09-23 09:00:00','2026-11-03 09:00:00',3,3,'Project discussion',0),
(2,4,1,2,'CRM migration','2026-09-23 09:00:00','2026-11-03 09:00:00',5,2,'Project discussion',0),
(3,6,1,3,'Route optimization','2026-09-23 09:00:00','2026-11-03 09:00:00',6,1,'Project discussion',0);
/*!40000 ALTER TABLE `dotp_forums` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_acl` (
  `id` int(11) NOT NULL DEFAULT 0,
  `section_value` varchar(80) NOT NULL DEFAULT 'system',
  `allow` int(11) NOT NULL DEFAULT 0,
  `enabled` int(11) NOT NULL DEFAULT 0,
  `return_value` longtext DEFAULT NULL,
  `note` longtext DEFAULT NULL,
  `updated_date` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `gacl_enabled_acl` (`enabled`),
  KEY `gacl_section_value_acl` (`section_value`),
  KEY `gacl_updated_date_acl` (`updated_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_acl` WRITE;
DELETE FROM `dotp_gacl_acl`;
/*!40000 ALTER TABLE `dotp_gacl_acl` DISABLE KEYS */;
INSERT INTO `dotp_gacl_acl` VALUES
(1,'system',1,1,'user','All roles: Login',1793751866),
(2,'system',1,1,'user','Administrator: ALL permissions ALL modules',1793751866),
(3,'system',1,1,'user','Administrator: access on ACL object',1793751866),
(4,'system',1,1,'user','Guest: access+view on non-admin modules',1793751866),
(5,'system',1,1,'user','Anonymous: access on non-admin modules',1793751866),
(6,'system',1,1,'user','Worker: ALL permissions on non-admin modules',1793751866),
(7,'system',1,1,'user','Worker+Guest: access+view on users',1793751866);
/*!40000 ALTER TABLE `dotp_gacl_acl` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_acl_sections` (
  `id` int(11) NOT NULL DEFAULT 0,
  `value` varchar(80) NOT NULL DEFAULT '',
  `order_value` int(11) NOT NULL DEFAULT 0,
  `name` varchar(230) NOT NULL DEFAULT '',
  `hidden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gacl_value_acl_sections` (`value`),
  KEY `gacl_hidden_acl_sections` (`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_acl_sections` WRITE;
DELETE FROM `dotp_gacl_acl_sections`;
/*!40000 ALTER TABLE `dotp_gacl_acl_sections` DISABLE KEYS */;
INSERT INTO `dotp_gacl_acl_sections` VALUES
(1,'system',1,'System',0),
(2,'user',2,'User',0);
/*!40000 ALTER TABLE `dotp_gacl_acl_sections` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_acl_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_acl_seq` WRITE;
DELETE FROM `dotp_gacl_acl_seq`;
/*!40000 ALTER TABLE `dotp_gacl_acl_seq` DISABLE KEYS */;
INSERT INTO `dotp_gacl_acl_seq` VALUES
(14);
/*!40000 ALTER TABLE `dotp_gacl_acl_seq` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aco` (
  `id` int(11) NOT NULL DEFAULT 0,
  `section_value` varchar(80) NOT NULL DEFAULT '0',
  `value` varchar(80) NOT NULL DEFAULT '',
  `order_value` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `hidden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gacl_section_value_value_aco` (`section_value`,`value`),
  KEY `gacl_hidden_aco` (`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aco` WRITE;
DELETE FROM `dotp_gacl_aco`;
/*!40000 ALTER TABLE `dotp_gacl_aco` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aco` VALUES
(1,'system','login',1,'Login',0),
(2,'application','access',1,'Access',0),
(3,'application','view',2,'View',0),
(4,'application','add',3,'Add',0),
(5,'application','edit',4,'Edit',0),
(6,'application','delete',5,'Delete',0);
/*!40000 ALTER TABLE `dotp_gacl_aco` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aco_map` (
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `section_value` varchar(80) NOT NULL DEFAULT '0',
  `value` varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`acl_id`,`section_value`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aco_map` WRITE;
DELETE FROM `dotp_gacl_aco_map`;
/*!40000 ALTER TABLE `dotp_gacl_aco_map` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aco_map` VALUES
(1,'system','login'),
(2,'application','access'),
(2,'application','add'),
(2,'application','delete'),
(2,'application','edit'),
(2,'application','view'),
(3,'application','access'),
(4,'application','access'),
(4,'application','view'),
(5,'application','access'),
(6,'application','access'),
(6,'application','add'),
(6,'application','delete'),
(6,'application','edit'),
(6,'application','view'),
(7,'application','access'),
(7,'application','view');
/*!40000 ALTER TABLE `dotp_gacl_aco_map` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aco_sections` (
  `id` int(11) NOT NULL DEFAULT 0,
  `value` varchar(80) NOT NULL DEFAULT '',
  `order_value` int(11) NOT NULL DEFAULT 0,
  `name` varchar(230) NOT NULL DEFAULT '',
  `hidden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gacl_value_aco_sections` (`value`),
  KEY `gacl_hidden_aco_sections` (`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aco_sections` WRITE;
DELETE FROM `dotp_gacl_aco_sections`;
/*!40000 ALTER TABLE `dotp_gacl_aco_sections` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aco_sections` VALUES
(1,'system',1,'System',0),
(2,'application',2,'Application',0);
/*!40000 ALTER TABLE `dotp_gacl_aco_sections` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aco_sections_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aco_sections_seq` WRITE;
DELETE FROM `dotp_gacl_aco_sections_seq`;
/*!40000 ALTER TABLE `dotp_gacl_aco_sections_seq` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aco_sections_seq` VALUES
(4);
/*!40000 ALTER TABLE `dotp_gacl_aco_sections_seq` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aco_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aco_seq` WRITE;
DELETE FROM `dotp_gacl_aco_seq`;
/*!40000 ALTER TABLE `dotp_gacl_aco_seq` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aco_seq` VALUES
(6);
/*!40000 ALTER TABLE `dotp_gacl_aco_seq` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aro` (
  `id` int(11) NOT NULL DEFAULT 0,
  `section_value` varchar(80) NOT NULL DEFAULT '0',
  `value` varchar(80) NOT NULL DEFAULT '',
  `order_value` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `hidden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gacl_section_value_value_aro` (`section_value`,`value`),
  KEY `gacl_hidden_aro` (`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aro` WRITE;
DELETE FROM `dotp_gacl_aro`;
/*!40000 ALTER TABLE `dotp_gacl_aro` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aro` VALUES
(1,'user','1',1,'admin',0),
(2,'user','2',1,'priya',0),
(3,'user','3',1,'marco',0),
(4,'user','4',1,'hannah',0),
(5,'user','5',1,'tomas',0),
(6,'user','6',1,'lena',0);
/*!40000 ALTER TABLE `dotp_gacl_aro` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aro_groups` (
  `id` int(11) NOT NULL DEFAULT 0,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `lft` int(11) NOT NULL DEFAULT 0,
  `rgt` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`,`value`),
  KEY `gacl_parent_id_aro_groups` (`parent_id`),
  KEY `gacl_value_aro_groups` (`value`),
  KEY `gacl_lft_rgt_aro_groups` (`lft`,`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aro_groups` WRITE;
DELETE FROM `dotp_gacl_aro_groups`;
/*!40000 ALTER TABLE `dotp_gacl_aro_groups` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aro_groups` VALUES
(1,0,1,12,'Roles','role'),
(2,1,2,3,'Administrator','admin'),
(3,1,4,5,'Anonymous','anon'),
(4,1,6,7,'Guest','guest'),
(5,1,8,9,'Project worker','normal');
/*!40000 ALTER TABLE `dotp_gacl_aro_groups` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aro_groups_id_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aro_groups_id_seq` WRITE;
DELETE FROM `dotp_gacl_aro_groups_id_seq`;
/*!40000 ALTER TABLE `dotp_gacl_aro_groups_id_seq` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aro_groups_id_seq` VALUES
(10);
/*!40000 ALTER TABLE `dotp_gacl_aro_groups_id_seq` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aro_groups_map` (
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`acl_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aro_groups_map` WRITE;
DELETE FROM `dotp_gacl_aro_groups_map`;
/*!40000 ALTER TABLE `dotp_gacl_aro_groups_map` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aro_groups_map` VALUES
(1,1),
(2,2),
(3,2),
(4,4),
(5,3),
(6,5),
(7,4),
(7,5);
/*!40000 ALTER TABLE `dotp_gacl_aro_groups_map` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aro_map` (
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `section_value` varchar(80) NOT NULL DEFAULT '0',
  `value` varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`acl_id`,`section_value`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aro_map` WRITE;
DELETE FROM `dotp_gacl_aro_map`;
/*!40000 ALTER TABLE `dotp_gacl_aro_map` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_gacl_aro_map` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aro_sections` (
  `id` int(11) NOT NULL DEFAULT 0,
  `value` varchar(80) NOT NULL DEFAULT '',
  `order_value` int(11) NOT NULL DEFAULT 0,
  `name` varchar(230) NOT NULL DEFAULT '',
  `hidden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gacl_value_aro_sections` (`value`),
  KEY `gacl_hidden_aro_sections` (`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aro_sections` WRITE;
DELETE FROM `dotp_gacl_aro_sections`;
/*!40000 ALTER TABLE `dotp_gacl_aro_sections` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aro_sections` VALUES
(1,'user',1,'Users',0);
/*!40000 ALTER TABLE `dotp_gacl_aro_sections` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aro_sections_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aro_sections_seq` WRITE;
DELETE FROM `dotp_gacl_aro_sections_seq`;
/*!40000 ALTER TABLE `dotp_gacl_aro_sections_seq` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aro_sections_seq` VALUES
(2);
/*!40000 ALTER TABLE `dotp_gacl_aro_sections_seq` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_aro_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_aro_seq` WRITE;
DELETE FROM `dotp_gacl_aro_seq`;
/*!40000 ALTER TABLE `dotp_gacl_aro_seq` DISABLE KEYS */;
INSERT INTO `dotp_gacl_aro_seq` VALUES
(6);
/*!40000 ALTER TABLE `dotp_gacl_aro_seq` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_axo` (
  `id` int(11) NOT NULL DEFAULT 0,
  `section_value` varchar(80) NOT NULL DEFAULT '0',
  `value` varchar(80) NOT NULL DEFAULT '',
  `order_value` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `hidden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gacl_section_value_value_axo` (`section_value`,`value`),
  KEY `gacl_hidden_axo` (`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_axo` WRITE;
DELETE FROM `dotp_gacl_axo`;
/*!40000 ALTER TABLE `dotp_gacl_axo` DISABLE KEYS */;
INSERT INTO `dotp_gacl_axo` VALUES
(1,'sys','acl',1,'ACL Administration',0),
(2,'app','admin',1,'User Administration',0),
(3,'app','calendar',2,'Calendar',0),
(4,'app','events',2,'Events',0),
(5,'app','companies',3,'Companies',0),
(6,'app','contacts',4,'Contacts',0),
(7,'app','departments',5,'Departments',0),
(8,'app','files',6,'Files',0),
(9,'app','file_folders',6,'File Folders',0),
(10,'app','forums',7,'Forums',0),
(11,'app','help',8,'Help',0),
(12,'app','projects',9,'Projects',0),
(13,'app','system',10,'System Administration',0),
(14,'app','tasks',11,'Tasks',0),
(15,'app','task_log',11,'Task Logs',0),
(16,'app','ticketsmith',12,'Tickets',0),
(17,'app','public',13,'Public',0),
(18,'app','roles',14,'Roles Administration',0),
(19,'app','users',15,'User Table',0),
(20,'app','dashboard',0,'Dashboard',0);
/*!40000 ALTER TABLE `dotp_gacl_axo` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_axo_groups` (
  `id` int(11) NOT NULL DEFAULT 0,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `lft` int(11) NOT NULL DEFAULT 0,
  `rgt` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`,`value`),
  KEY `gacl_parent_id_axo_groups` (`parent_id`),
  KEY `gacl_value_axo_groups` (`value`),
  KEY `gacl_lft_rgt_axo_groups` (`lft`,`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_axo_groups` WRITE;
DELETE FROM `dotp_gacl_axo_groups`;
/*!40000 ALTER TABLE `dotp_gacl_axo_groups` DISABLE KEYS */;
INSERT INTO `dotp_gacl_axo_groups` VALUES
(1,0,1,10,'Modules','mod'),
(2,1,2,3,'All Modules','all'),
(3,1,4,5,'Admin Modules','admin'),
(4,1,6,7,'Non-Admin Modules','non_admin');
/*!40000 ALTER TABLE `dotp_gacl_axo_groups` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_axo_groups_id_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_axo_groups_id_seq` WRITE;
DELETE FROM `dotp_gacl_axo_groups_id_seq`;
/*!40000 ALTER TABLE `dotp_gacl_axo_groups_id_seq` DISABLE KEYS */;
INSERT INTO `dotp_gacl_axo_groups_id_seq` VALUES
(8);
/*!40000 ALTER TABLE `dotp_gacl_axo_groups_id_seq` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_axo_groups_map` (
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`acl_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_axo_groups_map` WRITE;
DELETE FROM `dotp_gacl_axo_groups_map`;
/*!40000 ALTER TABLE `dotp_gacl_axo_groups_map` DISABLE KEYS */;
INSERT INTO `dotp_gacl_axo_groups_map` VALUES
(2,2),
(4,4),
(5,4),
(6,4);
/*!40000 ALTER TABLE `dotp_gacl_axo_groups_map` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_axo_map` (
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `section_value` varchar(80) NOT NULL DEFAULT '0',
  `value` varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`acl_id`,`section_value`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_axo_map` WRITE;
DELETE FROM `dotp_gacl_axo_map`;
/*!40000 ALTER TABLE `dotp_gacl_axo_map` DISABLE KEYS */;
INSERT INTO `dotp_gacl_axo_map` VALUES
(3,'sys','acl'),
(7,'app','users');
/*!40000 ALTER TABLE `dotp_gacl_axo_map` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_axo_sections` (
  `id` int(11) NOT NULL DEFAULT 0,
  `value` varchar(80) NOT NULL DEFAULT '',
  `order_value` int(11) NOT NULL DEFAULT 0,
  `name` varchar(230) NOT NULL DEFAULT '',
  `hidden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gacl_value_axo_sections` (`value`),
  KEY `gacl_hidden_axo_sections` (`hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_axo_sections` WRITE;
DELETE FROM `dotp_gacl_axo_sections`;
/*!40000 ALTER TABLE `dotp_gacl_axo_sections` DISABLE KEYS */;
INSERT INTO `dotp_gacl_axo_sections` VALUES
(1,'sys',1,'System',0),
(2,'app',2,'Application',0);
/*!40000 ALTER TABLE `dotp_gacl_axo_sections` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_axo_sections_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_axo_sections_seq` WRITE;
DELETE FROM `dotp_gacl_axo_sections_seq`;
/*!40000 ALTER TABLE `dotp_gacl_axo_sections_seq` DISABLE KEYS */;
INSERT INTO `dotp_gacl_axo_sections_seq` VALUES
(4);
/*!40000 ALTER TABLE `dotp_gacl_axo_sections_seq` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_axo_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_axo_seq` WRITE;
DELETE FROM `dotp_gacl_axo_seq`;
/*!40000 ALTER TABLE `dotp_gacl_axo_seq` DISABLE KEYS */;
INSERT INTO `dotp_gacl_axo_seq` VALUES
(20);
/*!40000 ALTER TABLE `dotp_gacl_axo_seq` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_groups_aro_map` (
  `group_id` int(11) NOT NULL DEFAULT 0,
  `aro_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`group_id`,`aro_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_groups_aro_map` WRITE;
DELETE FROM `dotp_gacl_groups_aro_map`;
/*!40000 ALTER TABLE `dotp_gacl_groups_aro_map` DISABLE KEYS */;
INSERT INTO `dotp_gacl_groups_aro_map` VALUES
(2,1),
(5,2),
(5,3),
(5,4),
(5,5),
(5,6);
/*!40000 ALTER TABLE `dotp_gacl_groups_aro_map` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_groups_axo_map` (
  `group_id` int(11) NOT NULL DEFAULT 0,
  `axo_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`group_id`,`axo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_groups_axo_map` WRITE;
DELETE FROM `dotp_gacl_groups_axo_map`;
/*!40000 ALTER TABLE `dotp_gacl_groups_axo_map` DISABLE KEYS */;
INSERT INTO `dotp_gacl_groups_axo_map` VALUES
(2,2),
(2,3),
(2,4),
(2,5),
(2,6),
(2,7),
(2,8),
(2,9),
(2,10),
(2,11),
(2,12),
(2,13),
(2,14),
(2,15),
(2,16),
(2,17),
(2,18),
(2,19),
(2,20),
(3,2),
(3,13),
(3,18),
(3,19),
(4,3),
(4,4),
(4,5),
(4,6),
(4,7),
(4,8),
(4,9),
(4,10),
(4,11),
(4,12),
(4,14),
(4,15),
(4,16),
(4,17),
(4,20);
/*!40000 ALTER TABLE `dotp_gacl_groups_axo_map` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_gacl_phpgacl` (
  `name` varchar(230) NOT NULL DEFAULT '',
  `value` varchar(230) NOT NULL DEFAULT '',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_gacl_phpgacl` WRITE;
DELETE FROM `dotp_gacl_phpgacl`;
/*!40000 ALTER TABLE `dotp_gacl_phpgacl` DISABLE KEYS */;
INSERT INTO `dotp_gacl_phpgacl` VALUES
('schema_version','2.1'),
('version','3.3.2');
/*!40000 ALTER TABLE `dotp_gacl_phpgacl` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_journal` (
  `journal_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `journal_user` int(10) NOT NULL DEFAULT 0,
  `journal_module` int(10) NOT NULL DEFAULT 0,
  `journal_project` int(10) NOT NULL DEFAULT 0,
  `journal_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `journal_description` text DEFAULT NULL,
  PRIMARY KEY (`journal_id`),
  UNIQUE KEY `journal_id` (`journal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_journal` WRITE;
DELETE FROM `dotp_journal`;
/*!40000 ALTER TABLE `dotp_journal` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_journal` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_modules` (
  `mod_id` int(11) NOT NULL AUTO_INCREMENT,
  `mod_name` varchar(64) NOT NULL DEFAULT '',
  `mod_directory` varchar(64) NOT NULL DEFAULT '',
  `mod_version` varchar(10) NOT NULL DEFAULT '',
  `mod_setup_class` varchar(64) NOT NULL DEFAULT '',
  `mod_type` varchar(64) NOT NULL DEFAULT '',
  `mod_active` int(1) unsigned NOT NULL DEFAULT 0,
  `mod_ui_name` varchar(20) NOT NULL DEFAULT '',
  `mod_ui_icon` varchar(64) NOT NULL DEFAULT '',
  `mod_ui_order` tinyint(3) NOT NULL DEFAULT 0,
  `mod_ui_active` int(1) unsigned NOT NULL DEFAULT 0,
  `mod_description` varchar(255) NOT NULL DEFAULT '',
  `permissions_item_table` char(100) DEFAULT NULL,
  `permissions_item_field` char(100) DEFAULT NULL,
  `permissions_item_label` char(100) DEFAULT NULL,
  PRIMARY KEY (`mod_id`,`mod_directory`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_modules` WRITE;
DELETE FROM `dotp_modules`;
/*!40000 ALTER TABLE `dotp_modules` DISABLE KEYS */;
INSERT INTO `dotp_modules` VALUES
(1,'Companies','companies','1.0.0','','core',1,'Companies','handshake.png',1,1,'','companies','company_id','company_name'),
(2,'Projects','projects','1.0.0','','core',1,'Projects','applet3-48.png',2,1,'','projects','project_id','project_name'),
(3,'Tasks','tasks','1.0.0','','core',1,'Tasks','applet-48.png',3,1,'','tasks','task_id','task_name'),
(4,'Calendar','calendar','1.0.0','','core',1,'Calendar','myevo-appointments.png',4,1,'','events','event_id','event_title'),
(5,'Files','files','1.0.0','','core',1,'Files','folder5.png',5,1,'','files','file_id','file_name'),
(6,'Contacts','contacts','1.0.0','','core',1,'Contacts','monkeychat-48.png',6,1,'','contacts','contact_id','contact_title'),
(7,'Forums','forums','1.0.0','','core',1,'Forums','support.png',7,1,'','forums','forum_id','forum_name'),
(8,'Tickets','ticketsmith','1.0.0','','core',1,'Tickets','ticketsmith.gif',8,1,'','','',''),
(9,'User Administration','admin','1.0.0','','core',1,'User Admin','helix-setup-users.png',9,1,'','users','user_id','user_username'),
(10,'System Administration','system','1.0.0','','core',1,'System Admin','48_my_computer.png',10,1,'','','',''),
(11,'Departments','departments','1.0.0','','core',1,'Departments','users.gif',11,0,'','departments','dept_id','dept_name'),
(12,'Help','help','1.0.0','','core',1,'Help','dp.gif',12,0,'','','',''),
(13,'Public','public','1.0.0','','core',1,'Public','users.gif',13,0,'','','',''),
(14,'Dashboard','dashboard','1.0.0','','core',1,'Dashboard','dashboard.png',0,1,'Personal dashboard','','','');
/*!40000 ALTER TABLE `dotp_modules` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_permissions` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_user` int(11) NOT NULL DEFAULT 0,
  `permission_grant_on` varchar(12) NOT NULL DEFAULT '',
  `permission_item` int(11) NOT NULL DEFAULT 0,
  `permission_value` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `idx_pgrant_on` (`permission_grant_on`,`permission_item`,`permission_user`),
  KEY `idx_puser` (`permission_user`),
  KEY `idx_pvalue` (`permission_value`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_permissions` WRITE;
DELETE FROM `dotp_permissions`;
/*!40000 ALTER TABLE `dotp_permissions` DISABLE KEYS */;
INSERT INTO `dotp_permissions` VALUES
(1,1,'all',-1,-1);
/*!40000 ALTER TABLE `dotp_permissions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_project_contacts` (
  `project_id` int(10) NOT NULL,
  `contact_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_project_contacts` WRITE;
DELETE FROM `dotp_project_contacts`;
/*!40000 ALTER TABLE `dotp_project_contacts` DISABLE KEYS */;
INSERT INTO `dotp_project_contacts` VALUES
(1,7),
(1,8),
(2,7),
(3,8),
(4,9),
(4,10),
(5,10),
(6,11),
(7,12);
/*!40000 ALTER TABLE `dotp_project_contacts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_project_departments` (
  `project_id` int(10) NOT NULL,
  `department_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_project_departments` WRITE;
DELETE FROM `dotp_project_departments`;
/*!40000 ALTER TABLE `dotp_project_departments` DISABLE KEYS */;
INSERT INTO `dotp_project_departments` VALUES
(1,1),
(2,2),
(3,1),
(4,1),
(5,2),
(6,1),
(7,2),
(8,2);
/*!40000 ALTER TABLE `dotp_project_departments` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_projects` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_company` int(11) NOT NULL DEFAULT 0,
  `project_company_internal` int(11) NOT NULL DEFAULT 0,
  `project_department` int(11) NOT NULL DEFAULT 0,
  `project_name` varchar(255) DEFAULT NULL,
  `project_short_name` varchar(10) DEFAULT NULL,
  `project_owner` int(11) DEFAULT 0,
  `project_url` varchar(255) DEFAULT NULL,
  `project_demo_url` varchar(255) DEFAULT NULL,
  `project_start_date` datetime DEFAULT NULL,
  `project_end_date` datetime DEFAULT NULL,
  `project_status` int(11) DEFAULT 0,
  `project_percent_complete` tinyint(4) DEFAULT 0,
  `project_color_identifier` varchar(6) DEFAULT 'eeeeee',
  `project_description` text DEFAULT NULL,
  `project_target_budget` decimal(10,2) DEFAULT 0.00,
  `project_actual_budget` decimal(10,2) DEFAULT 0.00,
  `project_creator` int(11) DEFAULT 0,
  `project_private` tinyint(3) unsigned DEFAULT 0,
  `project_departments` char(100) DEFAULT NULL,
  `project_contacts` char(100) DEFAULT NULL,
  `project_priority` tinyint(4) DEFAULT 0,
  `project_type` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`project_id`),
  KEY `idx_project_owner` (`project_owner`),
  KEY `idx_sdate` (`project_start_date`),
  KEY `idx_edate` (`project_end_date`),
  KEY `project_short_name` (`project_short_name`),
  KEY `idx_proj1` (`project_company`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_projects` WRITE;
DELETE FROM `dotp_projects`;
/*!40000 ALTER TABLE `dotp_projects` DISABLE KEYS */;
INSERT INTO `dotp_projects` VALUES
(1,2,1,1,'Warehouse Automation Rollout','ACME-WAR',2,NULL,NULL,'2026-09-09 09:00:00','2026-12-03 17:00:00',3,73,'1F77B4','Barcode scanning, conveyor sensors and a warehouse management system (WMS) integration for ACME\'s three warehouses, piloted in Building 3.',185000.00,0.00,2,0,NULL,NULL,1,2),
(2,2,1,2,'Safety Compliance Audit 2026','ACME-SCA',1,NULL,NULL,'2026-05-06 09:00:00','2026-05-28 17:00:00',5,100,'2CA02C','Yearly health-and-safety compliance audit of the three warehouses, with an action plan.',24000.00,0.00,1,0,NULL,NULL,0,1),
(3,2,1,1,'Customer Portal Redesign','ACME-CPR',2,NULL,NULL,'2026-12-02 09:00:00','2026-12-30 17:00:00',1,0,'9467BD','Proposal: a new order-tracking portal for ACME trade customers.',60000.00,0.00,2,0,NULL,NULL,0,2),
(4,3,1,1,'Scranton Branch CRM Migration','DM-CRM',2,NULL,NULL,'2026-09-23 09:00:00','2026-11-27 17:00:00',3,39,'D62728','Moving the Scranton sales team from spreadsheets and an old contact manager to a hosted CRM. Behind schedule: the customer data needed more cleaning than planned.',72000.00,0.00,2,0,NULL,NULL,1,2),
(5,3,1,2,'Paper Inventory Forecasting','DM-FCST',3,NULL,NULL,'2026-08-26 09:00:00','2026-12-25 17:00:00',4,39,'FF7F0E','On hold until the CRM migration is live: demand forecasts by paper grade from order history.',30000.00,0.00,3,0,NULL,NULL,-1,2),
(6,4,1,1,'Fleet Route Optimization','HV-ROUTE',3,NULL,NULL,'2026-10-23 09:00:00','2026-11-24 17:00:00',3,48,'17BECF','Daily route planning for 40 vans with delivery windows, replacing manual planning.',95000.00,0.00,3,0,NULL,NULL,0,2),
(7,5,1,2,'Digital Catalogue Upgrade','QC-CAT',4,NULL,NULL,'2026-11-09 09:00:00','2026-11-26 17:00:00',2,0,'8C564B','In planning: a new online catalogue with accessible search for the twelve branches.',48000.00,0.00,4,0,NULL,NULL,0,1),
(8,1,1,2,'Consultant Knowledge Base','BL-KB',1,NULL,NULL,'2026-10-07 09:00:00','2026-11-17 17:00:00',3,71,'7F7F7F','Internal: templates, checklists and lessons learned from client projects.',0.00,0.00,1,0,NULL,NULL,-1,1);
/*!40000 ALTER TABLE `dotp_projects` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_roles` (
  `role_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(24) NOT NULL DEFAULT '',
  `role_description` varchar(255) NOT NULL DEFAULT '',
  `role_type` int(3) unsigned NOT NULL DEFAULT 0,
  `role_module` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_roles` WRITE;
DELETE FROM `dotp_roles`;
/*!40000 ALTER TABLE `dotp_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_roles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_sessions` (
  `session_id` varchar(60) NOT NULL,
  `session_user` int(11) NOT NULL DEFAULT 0,
  `session_data` longblob DEFAULT NULL,
  `session_updated` timestamp NULL DEFAULT NULL,
  `session_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`session_id`),
  KEY `session_updated` (`session_updated`),
  KEY `session_created` (`session_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_sessions` WRITE;
DELETE FROM `dotp_sessions`;
/*!40000 ALTER TABLE `dotp_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_sessions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_syskeys` (
  `syskey_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `syskey_name` varchar(48) NOT NULL DEFAULT '',
  `syskey_label` varchar(255) NOT NULL DEFAULT '',
  `syskey_type` int(1) unsigned NOT NULL DEFAULT 0,
  `syskey_sep1` char(2) DEFAULT '\n',
  `syskey_sep2` char(2) NOT NULL DEFAULT '|',
  PRIMARY KEY (`syskey_id`),
  UNIQUE KEY `syskey_name` (`syskey_name`),
  UNIQUE KEY `idx_syskey_name` (`syskey_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_syskeys` WRITE;
DELETE FROM `dotp_syskeys`;
/*!40000 ALTER TABLE `dotp_syskeys` DISABLE KEYS */;
INSERT INTO `dotp_syskeys` VALUES
(1,'SelectList','Enter values for list',0,'\n','|'),
(2,'CustomField','Serialized array in the following format:\r\n<KEY>|<SERIALIZED ARRAY>\r\n\r\nSerialized Array:\r\n[type] => text | checkbox | select | textarea | label\r\n[name] => <Field\'s name>\r\n[options] => <html capture options>\r\n[selects] => <options for select and checkbox>',0,'\n','|'),
(3,'ColorSelection','Hex color values for type=>color association.',0,'\n','|');
/*!40000 ALTER TABLE `dotp_syskeys` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_sysvals` (
  `sysval_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sysval_key_id` int(10) unsigned NOT NULL DEFAULT 0,
  `sysval_title` varchar(48) NOT NULL DEFAULT '',
  `sysval_value` text NOT NULL,
  PRIMARY KEY (`sysval_id`),
  UNIQUE KEY `idx_sysval_title` (`sysval_title`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_sysvals` WRITE;
DELETE FROM `dotp_sysvals`;
/*!40000 ALTER TABLE `dotp_sysvals` DISABLE KEYS */;
INSERT INTO `dotp_sysvals` VALUES
(1,1,'ProjectStatus','0|Not Defined\r\n1|Proposed\r\n2|In Planning\r\n3|In Progress\r\n4|On Hold\r\n5|Complete\r\n6|Template\r\n7|Archived'),
(2,1,'CompanyType','0|Not Applicable\n1|Client\n2|Vendor\n3|Supplier\n4|Consultant\n5|Government\n6|Internal'),
(3,1,'TaskDurationType','1|hours\n24|days'),
(4,1,'EventType','0|General\n1|Appointment\n2|Meeting\n3|All Day Event\n4|Anniversary\n5|Reminder'),
(5,1,'TaskStatus','0|Active\n-1|Inactive'),
(6,1,'TaskType','0|Unknown\n1|Administrative\n2|Operative'),
(7,1,'ProjectType','0|Unknown\n1|Administrative\n2|Operative'),
(8,3,'ProjectColors','Web|FFE0AE\nEngineering|AEFFB2\nHelpDesk|FFFCAE\nSystem Administration|FFAEAE'),
(9,1,'FileType','0|Unknown\n1|Document\n2|Application'),
(10,1,'TaskPriority','-1|low\n0|normal\n1|high'),
(11,1,'ProjectPriority','-1|low\n0|normal\n1|high'),
(12,1,'ProjectPriorityColor','-1|#E5F7FF\n0|\n1|#FFDCB3'),
(13,1,'TaskLogReference','0|Not Defined\n1|Email\n2|Helpdesk\n3|Phone Call\n4|Fax'),
(14,1,'TaskLogReferenceImage','0| 1|./images/obj/email.gif 2|./modules/helpdesk/images/helpdesk.png 3|./images/obj/phone.gif 4|./images/icons/stock_print-16.png'),
(15,1,'UserType','0|Default User\r\n1|Administrator\r\n2|CEO\r\n3|Director\r\n4|Branch Manager\r\n5|Manager\r\n6|Supervisor\r\n7|Employee'),
(16,1,'ProjectRequiredFields','f.project_name.value.length|<3\r\nf.project_color_identifier.value.length|<3\r\nf.project_company.options[f.project_company.selectedIndex].value|<1'),
(17,2,'TicketNotify','0|admin@example.com\n1|admin@example.com\n2|admin@example.com\r\n3|admin@example.com\r\n4|admin@example.com'),
(18,1,'TicketPriority','0|Low\n1|Normal\n2|High\n3|Highest\n4|911'),
(19,1,'TicketStatus','0|Open\n1|Closed\n2|Deleted');
/*!40000 ALTER TABLE `dotp_sysvals` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_task_contacts` (
  `task_id` int(10) NOT NULL,
  `contact_id` int(10) NOT NULL,
  KEY `idx_task_contacts` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_task_contacts` WRITE;
DELETE FROM `dotp_task_contacts`;
/*!40000 ALTER TABLE `dotp_task_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_task_contacts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_task_departments` (
  `task_id` int(10) NOT NULL,
  `department_id` int(10) NOT NULL,
  KEY `idx_task_departments` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_task_departments` WRITE;
DELETE FROM `dotp_task_departments`;
/*!40000 ALTER TABLE `dotp_task_departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_task_departments` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_task_dependencies` (
  `dependencies_task_id` int(11) NOT NULL,
  `dependencies_req_task_id` int(11) NOT NULL,
  `dependencies_delay` int(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`dependencies_task_id`,`dependencies_req_task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_task_dependencies` WRITE;
DELETE FROM `dotp_task_dependencies`;
/*!40000 ALTER TABLE `dotp_task_dependencies` DISABLE KEYS */;
INSERT INTO `dotp_task_dependencies` VALUES
(3,2,0),
(4,3,0),
(6,4,0),
(7,4,0),
(9,7,0),
(10,6,0),
(11,7,0),
(12,9,0),
(12,10,0),
(14,12,0),
(15,12,0),
(16,14,0),
(16,15,0),
(18,17,0),
(19,18,0),
(20,19,0),
(22,21,0),
(23,22,0),
(25,24,0),
(26,25,0),
(27,24,0),
(28,26,0),
(28,27,0),
(29,28,0),
(30,29,0),
(32,31,0),
(33,32,0),
(36,34,0),
(36,35,0),
(37,36,0),
(40,38,0),
(40,39,0);
/*!40000 ALTER TABLE `dotp_task_dependencies` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_task_log` (
  `task_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_log_task` int(11) NOT NULL DEFAULT 0,
  `task_log_name` varchar(255) DEFAULT NULL,
  `task_log_description` text DEFAULT NULL,
  `task_log_creator` int(11) NOT NULL DEFAULT 0,
  `task_log_hours` float NOT NULL DEFAULT 0,
  `task_log_date` datetime DEFAULT NULL,
  `task_log_costcode` varchar(8) NOT NULL DEFAULT '',
  `task_log_problem` tinyint(1) DEFAULT 0,
  `task_log_reference` tinyint(4) DEFAULT 0,
  `task_log_related_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`task_log_id`),
  KEY `idx_log_task` (`task_log_task`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_task_log` WRITE;
DELETE FROM `dotp_task_log`;
/*!40000 ALTER TABLE `dotp_task_log` DISABLE KEYS */;
INSERT INTO `dotp_task_log` VALUES
(1,2,'Stakeholder interviews: kick-off','Started stakeholder interviews.',4,13.25,'2026-09-09 17:00:00','',0,0,''),
(2,2,'Stakeholder interviews','Progress on stakeholder interviews.',4,13.25,'2026-09-11 17:00:00','',0,0,''),
(3,2,'Stakeholder interviews','Progress on stakeholder interviews.',4,13.25,'2026-09-15 17:00:00','',0,0,''),
(4,3,'Current process mapping: kick-off','Started current process mapping.',4,9.5,'2026-09-16 17:00:00','',0,0,''),
(5,3,'Current process mapping','Progress on current process mapping.',4,9.5,'2026-09-18 17:00:00','',0,0,''),
(6,3,'Current process mapping','Progress on current process mapping.',4,9.5,'2026-09-22 17:00:00','',0,0,''),
(7,3,'Current process mapping: kick-off','Started current process mapping.',3,6.5,'2026-09-16 17:00:00','',0,0,''),
(8,3,'Current process mapping','Progress on current process mapping.',3,6.5,'2026-09-18 17:00:00','',0,0,''),
(9,3,'Current process mapping','Progress on current process mapping.',3,6.5,'2026-09-22 17:00:00','',0,0,''),
(10,6,'WMS integration design: kick-off','Started wms integration design.',3,16,'2026-09-25 17:00:00','',0,0,''),
(11,6,'WMS integration design','Progress on wms integration design.',3,16,'2026-09-29 17:00:00','',0,0,''),
(12,6,'WMS integration design','Progress on wms integration design.',3,16,'2026-10-01 17:00:00','',0,0,''),
(13,6,'WMS integration design','Progress on wms integration design.',3,16,'2026-10-05 17:00:00','',0,0,''),
(14,7,'Pilot site plan (Building 3): kick-off','Started pilot site plan (building 3).',2,8,'2026-10-01 17:00:00','',0,0,''),
(15,7,'Pilot site plan (Building 3)','Progress on pilot site plan (building 3).',2,8,'2026-10-05 17:00:00','',0,0,''),
(16,7,'Pilot site plan (Building 3): kick-off','Started pilot site plan (building 3).',4,8,'2026-10-01 17:00:00','',0,0,''),
(17,7,'Pilot site plan (Building 3)','Progress on pilot site plan (building 3).',4,8,'2026-10-05 17:00:00','',0,0,''),
(18,9,'Install scanners and conveyor sensors: kick-off','Started install scanners and conveyor sensors.',5,16,'2026-10-08 17:00:00','',0,0,''),
(19,9,'Install scanners and conveyor sensors','Progress on install scanners and conveyor sensors.',5,16,'2026-10-12 17:00:00','',0,0,''),
(20,9,'Install scanners and conveyor sensors','Progress on install scanners and conveyor sensors.',5,16,'2026-10-14 17:00:00','',0,0,''),
(21,9,'Install scanners and conveyor sensors','Progress on install scanners and conveyor sensors.',5,16,'2026-10-16 17:00:00','',0,0,''),
(22,9,'Install scanners and conveyor sensors','Progress on install scanners and conveyor sensors.',5,16,'2026-10-20 17:00:00','',0,0,''),
(23,9,'Install scanners and conveyor sensors','Progress on install scanners and conveyor sensors.',5,16,'2026-10-22 17:00:00','',0,0,''),
(24,10,'WMS integration build: kick-off','Started wms integration build.',5,8,'2026-10-08 17:00:00','',0,0,''),
(25,10,'WMS integration build','Progress on wms integration build.',5,8,'2026-10-12 17:00:00','',0,0,''),
(26,10,'WMS integration build','Progress on wms integration build.',5,8,'2026-10-14 17:00:00','',0,0,''),
(27,10,'WMS integration build','Progress on wms integration build.',5,8,'2026-10-16 17:00:00','',0,0,''),
(28,10,'WMS integration build','Progress on wms integration build.',5,8,'2026-10-20 17:00:00','',0,0,''),
(29,10,'WMS integration build','Progress on wms integration build.',5,8,'2026-10-22 17:00:00','',0,0,''),
(30,10,'WMS integration build','Progress on wms integration build.',5,8,'2026-10-26 17:00:00','',0,0,''),
(31,10,'WMS integration build','Progress on wms integration build.',5,8,'2026-10-28 17:00:00','',0,0,''),
(32,10,'WMS integration build','Progress on wms integration build.',5,8,'2026-10-30 17:00:00','',0,0,''),
(33,10,'WMS integration build','Progress on wms integration build.',5,8,'2026-11-03 17:00:00','',0,0,''),
(34,10,'WMS integration build: kick-off','Started wms integration build.',3,8,'2026-10-08 17:00:00','',0,0,''),
(35,10,'WMS integration build','Progress on wms integration build.',3,8,'2026-10-12 17:00:00','',0,0,''),
(36,10,'WMS integration build','Progress on wms integration build.',3,8,'2026-10-14 17:00:00','',0,0,''),
(37,10,'WMS integration build','Progress on wms integration build.',3,8,'2026-10-16 17:00:00','',0,0,''),
(38,10,'WMS integration build','Progress on wms integration build.',3,8,'2026-10-20 17:00:00','',0,0,''),
(39,10,'WMS integration build','Progress on wms integration build.',3,8,'2026-10-22 17:00:00','',0,0,''),
(40,10,'WMS integration build','Progress on wms integration build.',3,8,'2026-10-26 17:00:00','',0,0,''),
(41,10,'WMS integration build','Progress on wms integration build.',3,8,'2026-10-28 17:00:00','',0,0,''),
(42,10,'WMS integration build','Progress on wms integration build.',3,8,'2026-10-30 17:00:00','',0,0,''),
(43,10,'WMS integration build','Progress on wms integration build.',3,8,'2026-11-03 17:00:00','',0,0,''),
(44,11,'Picker handheld screens: kick-off','Started picker handheld screens.',6,17.25,'2026-10-21 17:00:00','',0,0,''),
(45,11,'Picker handheld screens','Progress on picker handheld screens.',6,17.25,'2026-10-23 17:00:00','',0,0,''),
(46,11,'Picker handheld screens','Progress on picker handheld screens.',6,17.25,'2026-10-27 17:00:00','',0,0,''),
(47,11,'Picker handheld screens','Progress on picker handheld screens.',6,17.25,'2026-10-29 17:00:00','',0,0,''),
(48,11,'Picker handheld screens','Progress on picker handheld screens.',6,17.25,'2026-11-02 17:00:00','',0,0,''),
(49,17,'Audit scope and checklist: kick-off','Started audit scope and checklist.',1,12,'2026-05-06 17:00:00','',0,0,''),
(50,17,'Audit scope and checklist','Progress on audit scope and checklist.',1,12,'2026-05-08 17:00:00','',0,0,''),
(51,18,'Site visits: kick-off','Started site visits.',4,16,'2026-05-11 17:00:00','',0,0,''),
(52,18,'Site visits','Progress on site visits.',4,16,'2026-05-13 17:00:00','',0,0,''),
(53,18,'Site visits','Progress on site visits.',4,16,'2026-05-15 17:00:00','',0,0,''),
(54,18,'Site visits','Progress on site visits.',4,16,'2026-05-19 17:00:00','',0,0,''),
(55,19,'Findings report: kick-off','Started findings report.',4,6.75,'2026-05-21 17:00:00','',0,0,''),
(56,19,'Findings report','Progress on findings report.',4,6.75,'2026-05-25 17:00:00','',0,0,''),
(57,19,'Findings report','Progress on findings report.',4,6.75,'2026-05-27 17:00:00','',0,0,''),
(58,19,'Findings report: kick-off','Started findings report.',1,6.75,'2026-05-21 17:00:00','',0,0,''),
(59,19,'Findings report','Progress on findings report.',1,6.75,'2026-05-25 17:00:00','',0,0,''),
(60,19,'Findings report','Progress on findings report.',1,6.75,'2026-05-27 17:00:00','',0,0,''),
(61,24,'Customer data audit: kick-off','Started customer data audit.',4,16,'2026-09-23 17:00:00','',0,0,''),
(62,24,'Customer data audit','Progress on customer data audit.',4,16,'2026-09-25 17:00:00','',0,0,''),
(63,24,'Customer data audit','Progress on customer data audit.',4,16,'2026-09-29 17:00:00','',0,0,''),
(64,25,'Field mapping and clean-up rules: kick-off','Started field mapping and clean-up rules.',4,5.5,'2026-10-01 17:00:00','',0,0,''),
(65,25,'Field mapping and clean-up rules','Progress on field mapping and clean-up rules.',4,5.5,'2026-10-05 17:00:00','',0,0,''),
(66,25,'Field mapping and clean-up rules','Progress on field mapping and clean-up rules.',4,5.5,'2026-10-07 17:00:00','',0,0,''),
(67,25,'Field mapping and clean-up rules','Progress on field mapping and clean-up rules.',4,5.5,'2026-10-09 17:00:00','',1,0,''),
(68,25,'Field mapping and clean-up rules: kick-off','Started field mapping and clean-up rules.',5,5.5,'2026-10-01 17:00:00','',0,0,''),
(69,25,'Field mapping and clean-up rules','Progress on field mapping and clean-up rules.',5,5.5,'2026-10-05 17:00:00','',0,0,''),
(70,25,'Field mapping and clean-up rules','Progress on field mapping and clean-up rules.',5,5.5,'2026-10-07 17:00:00','',0,0,''),
(71,25,'Field mapping and clean-up rules','Progress on field mapping and clean-up rules.',5,5.5,'2026-10-09 17:00:00','',1,0,''),
(72,27,'CRM configuration (pipelines, quotes): kick-off','Started crm configuration (pipelines, quotes).',3,12.75,'2026-10-14 17:00:00','',0,0,''),
(73,27,'CRM configuration (pipelines, quotes)','Progress on crm configuration (pipelines, quotes).',3,12.75,'2026-10-16 17:00:00','',0,0,''),
(74,27,'CRM configuration (pipelines, quotes)','Progress on crm configuration (pipelines, quotes).',3,12.75,'2026-10-20 17:00:00','',0,0,''),
(75,27,'CRM configuration (pipelines, quotes)','Progress on crm configuration (pipelines, quotes).',3,12.75,'2026-10-22 17:00:00','',0,0,''),
(76,27,'CRM configuration (pipelines, quotes)','Progress on crm configuration (pipelines, quotes).',3,12.75,'2026-10-26 17:00:00','',0,0,''),
(77,31,'Order history extract: kick-off','Started order history extract.',3,13.25,'2026-08-26 17:00:00','',0,0,''),
(78,31,'Order history extract','Progress on order history extract.',3,13.25,'2026-08-28 17:00:00','',0,0,''),
(79,31,'Order history extract','Progress on order history extract.',3,13.25,'2026-09-01 17:00:00','',0,0,''),
(80,32,'Forecast model prototype: kick-off','Started forecast model prototype.',3,6.5,'2026-09-02 17:00:00','',0,0,''),
(81,32,'Forecast model prototype','Progress on forecast model prototype.',3,6.5,'2026-09-04 17:00:00','',0,0,''),
(82,32,'Forecast model prototype','Progress on forecast model prototype.',3,6.5,'2026-09-08 17:00:00','',0,0,''),
(83,32,'Forecast model prototype','Progress on forecast model prototype.',3,6.5,'2026-09-10 17:00:00','',0,0,''),
(84,32,'Forecast model prototype','Progress on forecast model prototype.',3,6.5,'2026-09-14 17:00:00','',0,0,''),
(85,34,'Delivery data collection: kick-off','Started delivery data collection.',4,16,'2026-10-23 17:00:00','',0,0,''),
(86,34,'Delivery data collection','Progress on delivery data collection.',4,16,'2026-10-27 17:00:00','',0,0,''),
(87,34,'Delivery data collection','Progress on delivery data collection.',4,16,'2026-10-29 17:00:00','',0,0,''),
(88,35,'Routing engine evaluation: kick-off','Started routing engine evaluation.',3,17,'2026-10-27 17:00:00','',0,0,''),
(89,35,'Routing engine evaluation','Progress on routing engine evaluation.',3,17,'2026-10-29 17:00:00','',0,0,''),
(90,35,'Routing engine evaluation','Progress on routing engine evaluation.',3,17,'2026-11-02 17:00:00','',0,0,''),
(91,41,'Project templates: kick-off','Started project templates.',2,15,'2026-10-07 17:00:00','',0,0,''),
(92,41,'Project templates','Progress on project templates.',2,15,'2026-10-09 17:00:00','',0,0,''),
(93,41,'Project templates','Progress on project templates.',2,15,'2026-10-13 17:00:00','',0,0,''),
(94,41,'Project templates','Progress on project templates.',2,15,'2026-10-15 17:00:00','',0,0,''),
(95,41,'Project templates','Progress on project templates.',2,15,'2026-10-19 17:00:00','',0,0,''),
(96,41,'Project templates','Progress on project templates.',2,15,'2026-10-21 17:00:00','',0,0,''),
(97,41,'Project templates','Progress on project templates.',2,15,'2026-10-23 17:00:00','',0,0,''),
(98,41,'Project templates','Progress on project templates.',2,15,'2026-10-27 17:00:00','',0,0,''),
(99,42,'Lessons learned write-ups: kick-off','Started lessons learned write-ups.',1,8,'2026-10-21 17:00:00','',0,0,''),
(100,42,'Lessons learned write-ups','Progress on lessons learned write-ups.',1,8,'2026-10-23 17:00:00','',0,0,''),
(101,42,'Lessons learned write-ups','Progress on lessons learned write-ups.',1,8,'2026-10-27 17:00:00','',0,0,''),
(102,42,'Lessons learned write-ups','Progress on lessons learned write-ups.',1,8,'2026-10-29 17:00:00','',0,0,''),
(103,42,'Lessons learned write-ups','Progress on lessons learned write-ups.',1,8,'2026-11-02 17:00:00','',0,0,''),
(104,42,'Lessons learned write-ups: kick-off','Started lessons learned write-ups.',4,8,'2026-10-21 17:00:00','',0,0,''),
(105,42,'Lessons learned write-ups','Progress on lessons learned write-ups.',4,8,'2026-10-23 17:00:00','',0,0,''),
(106,42,'Lessons learned write-ups','Progress on lessons learned write-ups.',4,8,'2026-10-27 17:00:00','',0,0,''),
(107,42,'Lessons learned write-ups','Progress on lessons learned write-ups.',4,8,'2026-10-29 17:00:00','',0,0,''),
(108,42,'Lessons learned write-ups','Progress on lessons learned write-ups.',4,8,'2026-11-02 17:00:00','',0,0,'');
/*!40000 ALTER TABLE `dotp_task_log` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_tasks` (
  `task_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_name` varchar(255) DEFAULT NULL,
  `task_parent` int(11) DEFAULT 0,
  `task_milestone` tinyint(1) DEFAULT 0,
  `task_project` int(11) NOT NULL DEFAULT 0,
  `task_owner` int(11) NOT NULL DEFAULT 0,
  `task_start_date` datetime DEFAULT NULL,
  `task_duration` float unsigned DEFAULT 0,
  `task_duration_type` int(11) NOT NULL DEFAULT 1,
  `task_hours_worked` float unsigned DEFAULT 0,
  `task_end_date` datetime DEFAULT NULL,
  `task_status` int(11) DEFAULT 0,
  `task_priority` tinyint(4) DEFAULT 0,
  `task_percent_complete` decimal(5,2) DEFAULT 0.00,
  `task_description` text DEFAULT NULL,
  `task_target_budget` decimal(10,2) DEFAULT 0.00,
  `task_related_url` varchar(255) DEFAULT NULL,
  `task_creator` int(11) NOT NULL DEFAULT 0,
  `task_order` int(11) NOT NULL DEFAULT 0,
  `task_client_publish` tinyint(1) NOT NULL DEFAULT 0,
  `task_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `task_access` int(11) NOT NULL DEFAULT 0,
  `task_notify` int(11) NOT NULL DEFAULT 0,
  `task_departments` char(100) DEFAULT NULL,
  `task_contacts` char(100) DEFAULT NULL,
  `task_custom` longtext DEFAULT NULL,
  `task_type` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`task_id`),
  KEY `idx_task_parent` (`task_parent`),
  KEY `idx_task_project` (`task_project`),
  KEY `idx_task_owner` (`task_owner`),
  KEY `idx_task_order` (`task_order`),
  KEY `idx_task1` (`task_start_date`),
  KEY `idx_task2` (`task_end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_tasks` WRITE;
DELETE FROM `dotp_tasks`;
/*!40000 ALTER TABLE `dotp_tasks` DISABLE KEYS */;
INSERT INTO `dotp_tasks` VALUES
(1,'Discovery',1,0,1,2,'2026-09-09 09:00:00',11,24,87.75,'2026-09-24 09:00:00',0,0,100.00,'',0.00,'',2,0,0,1,0,0,NULL,NULL,NULL,2),
(2,'Stakeholder interviews',1,0,1,4,'2026-09-09 09:00:00',5,24,0,'2026-09-15 17:00:00',0,0,100.00,'Warehouse leads, IT, safety and finance.',0.00,NULL,2,1,0,0,0,0,NULL,NULL,NULL,2),
(3,'Current process mapping',1,0,1,4,'2026-09-16 09:00:00',6,24,0,'2026-09-23 17:00:00',0,0,100.00,'',0.00,NULL,2,2,0,0,0,0,NULL,NULL,NULL,2),
(4,'Requirements signed off',1,1,1,2,'2026-09-24 09:00:00',0,24,0,'2026-09-24 09:00:00',0,0,100.00,'',0.00,NULL,2,3,0,0,0,0,NULL,NULL,NULL,2),
(5,'Solution design',5,0,1,2,'2026-09-25 09:00:00',12,24,96,'2026-10-06 17:00:00',0,0,100.00,'',0.00,'',2,4,0,1,0,0,NULL,NULL,NULL,2),
(6,'WMS integration design',5,0,1,3,'2026-09-25 09:00:00',8,24,0,'2026-10-06 17:00:00',0,0,100.00,'',0.00,NULL,2,5,0,0,0,0,NULL,NULL,NULL,2),
(7,'Pilot site plan (Building 3)',5,0,1,2,'2026-10-01 09:00:00',4,24,0,'2026-10-06 17:00:00',0,0,100.00,'',0.00,NULL,2,6,0,0,0,0,NULL,NULL,NULL,2),
(8,'Pilot in Building 3',8,0,1,2,'2026-10-08 09:00:00',46,24,342.25,'2026-11-10 09:00:00',0,0,93.00,'',0.00,'',2,7,0,1,0,0,NULL,NULL,NULL,2),
(9,'Install scanners and conveyor sensors',8,0,1,5,'2026-10-08 09:00:00',12,24,0,'2026-10-23 17:00:00',0,0,100.00,'',0.00,NULL,2,8,0,0,0,0,NULL,NULL,NULL,2),
(10,'WMS integration build',8,0,1,5,'2026-10-08 09:00:00',22,24,0,'2026-11-06 17:00:00',0,0,90.00,'Inbound, picking and dispatch messages between the WMS and the conveyor controller.',0.00,NULL,2,9,0,0,0,0,NULL,NULL,NULL,2),
(11,'Picker handheld screens',8,0,1,6,'2026-10-21 09:00:00',12,24,0,'2026-11-05 17:00:00',0,0,90.00,'',0.00,NULL,2,10,0,0,0,0,NULL,NULL,NULL,2),
(12,'Pilot go-live',8,1,1,2,'2026-11-10 09:00:00',0,24,0,'2026-11-10 09:00:00',0,0,0.00,'',0.00,NULL,2,11,0,0,0,0,NULL,NULL,NULL,2),
(13,'Rollout',13,0,1,2,'2026-11-11 09:00:00',21,24,0,'2026-12-03 09:00:00',0,0,0.00,'',0.00,'',2,12,0,1,0,0,NULL,NULL,NULL,2),
(14,'Buildings 1 and 2 rollout',13,0,1,5,'2026-11-11 09:00:00',15,24,0,'2026-12-01 17:00:00',0,0,0.00,'',0.00,NULL,2,13,0,0,0,0,NULL,NULL,NULL,2),
(15,'Staff training',13,0,1,4,'2026-11-20 09:00:00',6,24,0,'2026-11-27 17:00:00',0,0,0.00,'',0.00,NULL,2,14,0,0,0,0,NULL,NULL,NULL,2),
(16,'Handover to ACME IT',13,1,1,2,'2026-12-03 09:00:00',0,24,0,'2026-12-03 09:00:00',0,0,0.00,'',0.00,NULL,2,15,0,0,0,0,NULL,NULL,NULL,2),
(17,'Audit scope and checklist',17,0,2,1,'2026-05-06 09:00:00',3,24,0,'2026-05-08 17:00:00',0,0,100.00,'',0.00,NULL,1,0,0,0,0,0,NULL,NULL,NULL,1),
(18,'Site visits',18,0,2,4,'2026-05-11 09:00:00',8,24,0,'2026-05-20 17:00:00',0,0,100.00,'',0.00,NULL,1,1,0,0,0,0,NULL,NULL,NULL,1),
(19,'Findings report',19,0,2,4,'2026-05-21 09:00:00',5,24,0,'2026-05-27 17:00:00',0,0,100.00,'',0.00,NULL,1,2,0,0,0,0,NULL,NULL,NULL,1),
(20,'Action plan agreed',20,1,2,1,'2026-05-28 09:00:00',0,24,0,'2026-05-28 09:00:00',0,0,100.00,'',0.00,NULL,1,3,0,0,0,0,NULL,NULL,NULL,1),
(21,'User research',21,0,3,6,'2026-12-02 09:00:00',8,24,0,'2026-12-11 17:00:00',0,0,0.00,'',0.00,NULL,2,0,0,0,0,0,NULL,NULL,NULL,2),
(22,'Clickable prototype',22,0,3,6,'2026-12-14 09:00:00',10,24,0,'2026-12-25 17:00:00',0,0,0.00,'',0.00,NULL,2,1,0,0,0,0,NULL,NULL,NULL,2),
(23,'Build estimate',23,0,3,2,'2026-12-28 09:00:00',3,24,0,'2026-12-30 17:00:00',0,0,0.00,'',0.00,NULL,2,2,0,0,0,0,NULL,NULL,NULL,2),
(24,'Customer data audit',24,0,4,4,'2026-09-23 09:00:00',6,24,0,'2026-09-30 17:00:00',0,0,100.00,'',0.00,NULL,2,0,0,0,0,0,NULL,NULL,NULL,2),
(25,'Field mapping and clean-up rules',25,0,4,4,'2026-10-01 09:00:00',8,24,0,'2026-10-12 17:00:00',0,0,70.00,'Duplicate customers across the two old systems: about 18% of records.',0.00,NULL,2,1,0,0,0,0,NULL,NULL,NULL,2),
(26,'Migration scripts',26,0,4,5,'2026-10-19 09:00:00',10,24,0,'2026-10-30 17:00:00',0,0,0.00,'',0.00,NULL,2,2,0,0,0,0,NULL,NULL,NULL,2),
(27,'CRM configuration (pipelines, quotes)',27,0,4,3,'2026-10-14 09:00:00',10,24,0,'2026-10-27 17:00:00',0,0,80.00,'',0.00,NULL,2,3,0,0,0,0,NULL,NULL,NULL,2),
(28,'User acceptance testing with the sales team',28,0,4,4,'2026-11-04 09:00:00',6,24,0,'2026-11-11 17:00:00',0,0,0.00,'',0.00,NULL,2,4,0,0,0,0,NULL,NULL,NULL,2),
(29,'Cut-over weekend',29,1,4,2,'2026-11-13 09:00:00',0,24,0,'2026-11-13 09:00:00',0,0,0.00,'',0.00,NULL,2,5,0,0,0,0,NULL,NULL,NULL,2),
(30,'Hypercare',30,0,4,5,'2026-11-16 09:00:00',10,24,0,'2026-11-27 17:00:00',0,0,0.00,'',0.00,NULL,2,6,0,0,0,0,NULL,NULL,NULL,2),
(31,'Order history extract',31,0,5,3,'2026-08-26 09:00:00',5,24,0,'2026-09-01 17:00:00',0,0,100.00,'',0.00,NULL,3,0,0,0,0,0,NULL,NULL,NULL,2),
(32,'Forecast model prototype',32,0,5,3,'2026-09-02 09:00:00',10,24,0,'2026-09-15 17:00:00',0,0,40.00,'',0.00,NULL,3,1,0,0,0,0,NULL,NULL,NULL,2),
(33,'Forecast dashboard',33,0,5,6,'2026-12-16 09:00:00',8,24,0,'2026-12-25 17:00:00',0,0,0.00,'',0.00,NULL,3,2,0,0,0,0,NULL,NULL,NULL,2),
(34,'Delivery data collection',34,0,6,4,'2026-10-23 09:00:00',6,24,0,'2026-10-30 17:00:00',0,0,100.00,'',0.00,NULL,3,0,0,0,0,0,NULL,NULL,NULL,2),
(35,'Routing engine evaluation',35,0,6,3,'2026-10-27 09:00:00',8,24,0,'2026-11-05 17:00:00',0,0,80.00,'',0.00,NULL,3,1,0,0,0,0,NULL,NULL,NULL,2),
(36,'Proof of concept on one depot',36,0,6,3,'2026-11-06 09:00:00',12,24,0,'2026-11-23 17:00:00',0,0,0.00,'',0.00,NULL,3,2,0,0,0,0,NULL,NULL,NULL,2),
(37,'Go / no-go decision',37,1,6,1,'2026-11-24 09:00:00',0,24,0,'2026-11-24 09:00:00',0,0,0.00,'',0.00,NULL,3,3,0,0,0,0,NULL,NULL,NULL,2),
(38,'Accessibility review of the current catalogue',38,0,7,6,'2026-11-09 09:00:00',5,24,0,'2026-11-13 17:00:00',0,0,0.00,'',0.00,NULL,4,0,0,0,0,0,NULL,NULL,NULL,1),
(39,'Vendor shortlist',39,0,7,4,'2026-11-09 09:00:00',8,24,0,'2026-11-18 17:00:00',0,0,0.00,'',0.00,NULL,4,1,0,0,0,0,NULL,NULL,NULL,1),
(40,'Tender documents',40,0,7,4,'2026-11-19 09:00:00',6,24,0,'2026-11-26 17:00:00',0,0,0.00,'',0.00,NULL,4,2,0,0,0,0,NULL,NULL,NULL,1),
(41,'Project templates',41,0,8,2,'2026-10-07 09:00:00',15,24,0,'2026-10-27 17:00:00',0,0,100.00,'',0.00,NULL,1,0,0,0,0,0,NULL,NULL,NULL,1),
(42,'Lessons learned write-ups',42,0,8,1,'2026-10-21 09:00:00',20,24,0,'2026-11-17 17:00:00',0,0,50.00,'',0.00,NULL,1,1,0,0,0,0,NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `dotp_tasks` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_tasks_ical` (
  `task_id` int(10) NOT NULL,
  `UID` varchar(30) DEFAULT NULL,
  `created` varchar(15) NOT NULL,
  `sequence` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_tasks_ical` WRITE;
DELETE FROM `dotp_tasks_ical`;
/*!40000 ALTER TABLE `dotp_tasks_ical` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_tasks_ical` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_tickets` (
  `ticket` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_company` int(10) NOT NULL DEFAULT 0,
  `ticket_project` int(10) NOT NULL DEFAULT 0,
  `author` varchar(100) NOT NULL DEFAULT '',
  `recipient` varchar(100) NOT NULL DEFAULT '',
  `subject` varchar(100) NOT NULL DEFAULT '',
  `attachment` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `timestamp` int(10) unsigned NOT NULL DEFAULT 0,
  `type` varchar(15) NOT NULL DEFAULT '',
  `assignment` int(10) unsigned NOT NULL DEFAULT 0,
  `parent` int(10) unsigned NOT NULL DEFAULT 0,
  `activity` int(10) unsigned NOT NULL DEFAULT 0,
  `priority` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `cc` varchar(255) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `signature` text DEFAULT NULL,
  PRIMARY KEY (`ticket`),
  KEY `parent` (`parent`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_tickets` WRITE;
DELETE FROM `dotp_tickets`;
/*!40000 ALTER TABLE `dotp_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_tickets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_user_access_log` (
  `user_access_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `user_ip` varchar(15) NOT NULL,
  `date_time_in` datetime DEFAULT '0000-00-00 00:00:00',
  `date_time_out` datetime DEFAULT '0000-00-00 00:00:00',
  `date_time_last_action` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`user_access_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_user_access_log` WRITE;
DELETE FROM `dotp_user_access_log`;
/*!40000 ALTER TABLE `dotp_user_access_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_user_access_log` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_user_events` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `event_id` int(11) NOT NULL DEFAULT 0,
  KEY `uek1` (`user_id`,`event_id`),
  KEY `uek2` (`event_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_user_events` WRITE;
DELETE FROM `dotp_user_events`;
/*!40000 ALTER TABLE `dotp_user_events` DISABLE KEYS */;
INSERT INTO `dotp_user_events` VALUES
(1,5),
(1,7),
(1,9),
(1,10),
(2,1),
(2,2),
(2,3),
(2,4),
(2,5),
(2,7),
(2,10),
(3,1),
(3,2),
(3,3),
(3,4),
(3,5),
(3,8),
(3,10),
(4,6),
(4,8),
(4,9),
(4,10),
(5,1),
(5,2),
(5,3),
(5,4),
(5,5),
(5,6),
(5,10),
(6,9),
(6,10);
/*!40000 ALTER TABLE `dotp_user_events` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_user_preferences` (
  `pref_user` varchar(12) NOT NULL DEFAULT '',
  `pref_name` varchar(72) NOT NULL DEFAULT '',
  `pref_value` varchar(32) NOT NULL DEFAULT '',
  KEY `pref_user` (`pref_user`,`pref_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_user_preferences` WRITE;
DELETE FROM `dotp_user_preferences`;
/*!40000 ALTER TABLE `dotp_user_preferences` DISABLE KEYS */;
INSERT INTO `dotp_user_preferences` VALUES
('0','LOCALE','en'),
('0','TABVIEW','0'),
('0','SHDATEFORMAT','%d/%m/%Y'),
('0','TIMEFORMAT','%I:%M %p'),
('0','UISTYLE','dp-grey-theme'),
('0','TASKASSIGNMAX','100'),
('0','TASKASSIGNMAIL','1'),
('0','USERFORMAT','user');
/*!40000 ALTER TABLE `dotp_user_preferences` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_user_task_pin` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `task_id` int(10) NOT NULL DEFAULT 0,
  `task_pinned` tinyint(2) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`,`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_user_task_pin` WRITE;
DELETE FROM `dotp_user_task_pin`;
/*!40000 ALTER TABLE `dotp_user_task_pin` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_user_task_pin` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_user_tasks` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `user_type` tinyint(4) NOT NULL DEFAULT 0,
  `task_id` int(11) NOT NULL DEFAULT 0,
  `perc_assignment` int(11) NOT NULL DEFAULT 100,
  `user_task_priority` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`user_id`,`task_id`),
  KEY `user_type` (`user_type`),
  KEY `idx_user_tasks` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_user_tasks` WRITE;
DELETE FROM `dotp_user_tasks`;
/*!40000 ALTER TABLE `dotp_user_tasks` DISABLE KEYS */;
INSERT INTO `dotp_user_tasks` VALUES
(1,0,17,100,0),
(1,0,19,50,0),
(1,0,20,100,0),
(1,0,37,100,0),
(1,0,40,40,0),
(1,0,42,50,0),
(2,0,4,100,0),
(2,0,7,50,0),
(2,0,12,100,0),
(2,0,16,100,0),
(2,0,23,100,0),
(2,0,28,50,0),
(2,0,29,100,0),
(2,0,41,100,0),
(3,0,3,40,0),
(3,0,6,100,0),
(3,0,10,50,0),
(3,0,14,50,0),
(3,0,27,100,0),
(3,0,31,100,0),
(3,0,32,100,0),
(3,0,35,100,0),
(3,0,36,50,0),
(4,0,2,100,0),
(4,0,3,60,0),
(4,0,7,50,0),
(4,0,15,100,0),
(4,0,18,100,0),
(4,0,19,50,0),
(4,0,24,100,0),
(4,0,25,50,0),
(4,0,28,50,0),
(4,0,30,50,0),
(4,0,34,100,0),
(4,0,39,100,0),
(4,0,40,60,0),
(4,0,42,50,0),
(5,0,9,100,0),
(5,0,10,50,0),
(5,0,14,50,0),
(5,0,22,30,0),
(5,0,25,50,0),
(5,0,26,100,0),
(5,0,30,50,0),
(5,0,36,50,0),
(6,0,11,100,0),
(6,0,21,100,0),
(6,0,22,70,0),
(6,0,33,100,0),
(6,0,38,100,0);
/*!40000 ALTER TABLE `dotp_user_tasks` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_user_twofactor` (
  `user_id` int(11) NOT NULL,
  `tf_secret` varchar(64) NOT NULL,
  `tf_last_counter` bigint(20) NOT NULL DEFAULT 0,
  `tf_enabled_on` datetime NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_user_twofactor` WRITE;
DELETE FROM `dotp_user_twofactor`;
/*!40000 ALTER TABLE `dotp_user_twofactor` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_user_twofactor` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_user_twofactor_recovery` (
  `rc_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `rc_hash` char(64) NOT NULL,
  `rc_used_on` datetime DEFAULT NULL,
  PRIMARY KEY (`rc_id`),
  KEY `idx_rc_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_user_twofactor_recovery` WRITE;
DELETE FROM `dotp_user_twofactor_recovery`;
/*!40000 ALTER TABLE `dotp_user_twofactor_recovery` DISABLE KEYS */;
/*!40000 ALTER TABLE `dotp_user_twofactor_recovery` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `dotp_users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_contact` int(11) NOT NULL DEFAULT 0,
  `user_username` varchar(255) NOT NULL DEFAULT '',
  `user_password` varchar(255) NOT NULL DEFAULT '',
  `user_parent` int(11) NOT NULL DEFAULT 0,
  `user_type` tinyint(3) NOT NULL DEFAULT 0,
  `user_company` int(11) DEFAULT 0,
  `user_department` int(11) DEFAULT 0,
  `user_owner` int(11) NOT NULL DEFAULT 0,
  `user_signature` text DEFAULT NULL,
  `user_reset_token` varchar(255) DEFAULT NULL,
  `user_reset_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_uid` (`user_username`),
  KEY `idx_pwd` (`user_password`),
  KEY `idx_user_parent` (`user_parent`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dotp_users` WRITE;
DELETE FROM `dotp_users`;
/*!40000 ALTER TABLE `dotp_users` DISABLE KEYS */;
INSERT INTO `dotp_users` VALUES
(1,1,'admin','76a2173be6393254e72ffa4d6df1030a',0,1,1,2,0,'',NULL,NULL),
(2,2,'priya','$2y$10$L7vXMNghQ91lTIsf0oZ0uOljiOblt3BXHYxSl/OoyOuORB2XISBBO',0,0,1,1,1,NULL,NULL,NULL),
(3,3,'marco','$2y$10$rOXFuZd56G/ah4pA/36NQObC3keezvVu2cMZngb2eS6WyR6DY8r6W',0,0,1,1,1,NULL,NULL,NULL),
(4,4,'hannah','$2y$10$G36TpQxDOhpUCEOzo96on.IhjxLzD3gALPCUAuLorFlGb6fBSAh4q',0,0,1,2,1,NULL,NULL,NULL),
(5,5,'tomas','$2y$10$KSrpc8r7adRGjvcCaLceU.8ZMT.E8B69dC1LzuLGtKjD4E99DXIyK',0,0,1,1,1,NULL,NULL,NULL),
(6,6,'lena','$2y$10$YwhGbCxiX9G7ACO/uE0AW.oLRs6dqRHx0jcqmcDboXP8lEjXzaEpm',0,0,1,1,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `dotp_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

