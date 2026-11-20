CREATE TABLE `domains` (
  `domain_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `domain_name` varchar(150) NOT NULL,
  `domain_expiry_date` date NOT NULL,
  `domain_registrar` varchar(150) NOT NULL,
  `domain_status` varchar(150) NOT NULL,
  `domain_notes` text NOT NULL,
  PRIMARY KEY  (`domain_id`)
) ENGINE=InnoDB;

CREATE TABLE `hosting` (
  `hosting_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL,
  `hosting_package_name` varchar(150) NOT NULL,
  `hosting_expiry_date` date NOT NULL,
  `hosting_status` varchar(150) NOT NULL,
  `hosting_notes` text NOT NULL,
  PRIMARY KEY  (`hosting_id`)
) ENGINE=InnoDB;
