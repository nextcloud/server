
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `locks` (
  `token` varchar(255) NOT NULL DEFAULT '',
  `path` varchar(200) NOT NULL DEFAULT '',
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
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) NOT NULL,
  `user` varchar(250) NOT NULL,
  `type` int(11) NOT NULL,
  `message` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;


CREATE TABLE IF NOT EXISTS `properties` (
  `path` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(120) NOT NULL DEFAULT '',
  `ns` varchar(120) NOT NULL DEFAULT 'DAV:',
  `value` text,
  PRIMARY KEY (`path`,`name`,`ns`),
  KEY `path` (`path`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


