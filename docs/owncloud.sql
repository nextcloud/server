CREATE TABLE IF NOT EXISTS `locks` (
  `token` varchar(255) NOT NULL DEFAULT '',
  `path` varchar(200) NOT NULL DEFAULT '',
  `created` int(11) NOT NULL DEFAULT '0',
  `modified` int(11) NOT NULL DEFAULT '0',
  `expires` int(11) NOT NULL DEFAULT '0',
  `owner` varchar(200) DEFAULT NULL,
  `recursive` int(11) DEFAULT '0',
  `writelock` int(11) DEFAULT '0',
  `exclusivelock` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`token`),
  UNIQUE KEY `token` (`token`),
  KEY `path` (`path`),
  KEY `path_2` (`path`),
  KEY `path_3` (`path`,`token`),
  KEY `expires` (`expires`)
);

CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) NOT NULL,
  `user` varchar(250) NOT NULL,
  `type` int(11) NOT NULL,
  `message` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
);


CREATE TABLE IF NOT EXISTS `properties` (
  `path` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(120) NOT NULL DEFAULT '',
  `ns` varchar(120) NOT NULL DEFAULT 'DAV:',
  `value` text,
  PRIMARY KEY (`path`,`name`,`ns`),
  KEY `path` (`path`)
);

CREATE TABLE IF NOT EXISTS  `users` (
`user_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`user_name` VARCHAR( 64 ) NOT NULL ,
`user_name_clean` VARCHAR( 64 ) NOT NULL ,
`user_password` VARCHAR( 340) NOT NULL ,
UNIQUE (
`user_name` ,
`user_name_clean`
)
);

CREATE TABLE IF NOT EXISTS  `groups` (
`group_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`group_name` VARCHAR( 64 ) NOT NULL ,
UNIQUE (
`group_name`
)
);

CREATE TABLE IF NOT EXISTS  `user_group` (
`user_group_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`user_id` VARCHAR( 64 ) NOT NULL ,
`group_id` VARCHAR( 64 ) NOT NULL
)