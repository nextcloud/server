<?php

// in case there are private configurations in the users home -> use them
$privateConfigFile = $_SERVER['HOME'] . '/owncloud-extfs-test-config.php';
if (file_exists($privateConfigFile)) {
	$config = include($privateConfigFile);
	return $config;
}

// this is now more a template now for your private configurations
return array(
	'ftp'=>array(
		'run'=>false,
		'host'=>'localhost',
		'user'=>'test',
		'password'=>'test',
		'root'=>'/test',
	),
	'webdav'=>array(
		'run'=>false,
		'host'=>'localhost',
		'user'=>'test',
		'password'=>'test',
		'root'=>'/owncloud/files/webdav.php',
	),
	'google'=>array(
		'run'=>false,
		'consumer_key'=>'anonymous',
		'consumer_secret'=>'anonymous',
		'token'=>'test',
		'token_secret'=>'test',
		'root'=>'/google',
	),
	'swift'=>array(
		'run'=>false,
		'user'=>'test:tester',
		'token'=>'testing',
		'host'=>'localhost.local:8080/auth',
		'root'=>'/',
	),
	'smb'=>array(
		'run'=>false,
		'user'=>'test',
		'password'=>'test',
		'host'=>'localhost',
		'share'=>'/test',
		'root'=>'/test/',
	),
	'amazons3'=>array(
		'run'=>false,
		'key'=>'test',
		'secret'=>'test',
		'bucket'=>'bucket',
	),
	'dropbox' => array (
		'run'=>false,
		'root'=>'owncloud',
		'configured' => 'true',
		'app_key' => '',
		'app_secret' => '',
		'token' => '',
		'token_secret' => ''
	),
	'sftp' => array (
		'run'=>false,
		'host'=>'localhost',
		'user'=>'test',
		'password'=>'test',
		'root'=>'/test'
	)
);
