CREATE TABLE IF NOT EXISTS `#__yoti_users` (
    `joomla_userid` int(15) NOT NULL,
    `identifier` text NOT NULL,
    `data`  text NOT NULL,
    PRIMARY KEY (`joomla_userid`)
)