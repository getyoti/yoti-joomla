CREATE TABLE IF NOT EXISTS `#__yoti_users` (
    `joomla_userid` int(15) NOT NULL,
    `identifier` text NOT NULL,
    `nationality` varchar(255) DEFAULT NULL,
    `date_of_birth` VARCHAR(255) DEFAULT NULL,
    `selfie_filename` varchar(255) DEFAULT NULL,
    `phone_number` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`joomla_userid`)
)