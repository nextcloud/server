<?php

OC::$CLASSPATH['OC_Crypt'] = 'apps/files_encryption/lib/crypt.php';
OC::$CLASSPATH['OC_CryptStream'] = 'apps/files_encryption/lib/cryptstream.php';
OC::$CLASSPATH['OC_FileProxy_Encryption'] = 'apps/files_encryption/lib/proxy.php';

OC_FileProxy::register(new OC_FileProxy_Encryption());

OCP\Util::connectHook('OC_User','post_login','OC_Crypt','loginListener');

stream_wrapper_register('crypt','OC_CryptStream');

if(!isset($_SESSION['enckey']) and OCP\User::isLoggedIn()){//force the user to re-loggin if the encryption key isn't unlocked (happens when a user is logged in before the encryption app is enabled)
	OCP\User::logout();
	header("Location: ".OC::$WEBROOT.'/');
	exit();
}

OCP\App::registerAdmin('files_encryption', 'settings');
