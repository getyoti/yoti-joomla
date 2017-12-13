
CREATE DATABASE IF NOT EXISTS `yotijoomla`;

USE `yotijoomla`;

CREATE TABLE IF NOT EXISTS `yoti_yoti_users` (

  `joomla_userid` int(15) NOT NULL,

  `identifier` text NOT NULL,

  `data` text NOT NULL,

  PRIMARY KEY (`joomla_userid`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `yoti_assets` (`id`, `parent_id`, `lft`, `rgt`, `level`, `name`, `title`, `rules`)
VALUES (100, 18, 74, 75, 2, 'com_modules.module.188', 'Yoti Login', '{}'),
(101, 1, 107, 108, 1, 'com_yoti', 'com_yoti', '{}');


INSERT INTO `yoti_menu` (`menutype`, `title`, `alias`, `note`, `path`, `link`, `type`, `published`, `parent_id`, `level`, `component_id`, `checked_out`,  `checked_out_time`, `browserNav`, `access`, `img`, `template_style_id`, `params`, `lft`, `rgt`, `home`, `language`, `client_id`)
VALUES ('main', 'Yoti', X'796F7469', '', 'yoti', 'index.php?option=com_yoti', 'component', 1, 1, 1, 10000, 0, '1970-01-01 00:00:00', 0, 1, 'class:component', 0, '{}', 43, 44, 0, '', 1);


INSERT INTO `yoti_modules` (`id`, `asset_id`, `title`, `note`, `content`, `ordering`, `position`, `checked_out`, `checked_out_time`, `publish_up`, `publish_down`, `published`, `module`, `access`, `showtitle`, `params`, `client_id`, `language`)
VALUES (188, 100, 'Yoti Login', '', '', 0, '', 0, '1970-01-01 00:00:00', '1970-01-01 00:00:00', '1970-01-01 00:00:00', 0, 'mod_yoti', 1, 1, '', 0, '*');


INSERT INTO `yoti_update_sites` (`update_site_id`, `name`, `type`, `location`, `enabled`, `last_check_timestamp`)
VALUES (4, 'Yoti Updates', 'extension', 'https://github.com/getyoti/yoti-joomla/yoti-joomla.xml', 1, 0);


INSERT INTO `yoti_update_sites_extensions` (`update_site_id`, `extension_id`)
VALUES (4, 10000);


INSERT INTO `yoti_extensions` (`extension_id`, `package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`)
VALUES (10000, 0, 'com_yoti', 'component', 'com_yoti', '', 1, 1, 0, 0, '{\"name\":\"com_yoti\",\"type\":\"component\",\"creationDate\":\"August 2016\",\"author\":\"Yoti Ltd\",\"copyright\":\"Copyright (C) 2017 http:\\/\\/www.yoti.com. All rights reserved.\",\"authorEmail\":\"sdksupport@yoti.com\",\"authorUrl\":\"http:\\/\\/www.yoti.com\",\"version\":\"1.0.1\",\"description\":\"Let Yoti users quickly register on your site. Note: Need to enable Yoti module and Yoti plugin.\",\"group\":\"\",\"filename\":\"com_yoti\"}', '{}', '', '', 0, '1970-01-01 00:00:00', 0, 0),
(10001, 0, 'Yoti Login', 'module', 'mod_yoti', '', 0, 1, 0, 0, '{\"name\":\"Yoti Login\",\"type\":\"module\",\"creationDate\":\"August 2016\",\"author\":\"Yoti Ltd\",\"copyright\":\"Copyright (C) 2017 http:\\/\\/www.yoti.com. All rights reserved.\",\"authorEmail\":\"sdksupport@yoti.com\",\"authorUrl\":\"http:\\/\\/www.yoti.com\",\"version\":\"1.0.1\",\"description\":\"Yoti Module allows you to add a button to your page to connect to Yoti\",\"group\":\"\",\"filename\":\"mod_yoti\"}', '{}', '', '', 0, '1970-01-01 00:00:00', 0, 0),
(10002, 0, 'PLG_USER_YOTIPROFILE', 'plugin', 'yotiprofile', 'user', 0, 0, 1, 0, '{\"name\":\"PLG_USER_YOTIPROFILE\",\"type\":\"plugin\",\"creationDate\":\"August 2016\",\"author\":\"Yoti Ltd\",\"copyright\":\"Copyright (C) 2017 http:\\/\\/www.yoti.com. All rights reserved.\",\"authorEmail\":\"sdksupport@yoti.com\",\"authorUrl\":\"http:\\/\\/www.yoti.com\",\"version\":\"1.0.1\",\"description\":\"PLG_USER_YOTIPROFILE_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"yotiprofile\"}', '{}', '', '', 0, '1970-01-01 00:00:00', 0, 0);



