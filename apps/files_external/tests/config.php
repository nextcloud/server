<?php
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
