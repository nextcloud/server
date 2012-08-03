<?php

OC::$CLASSPATH['OCA_Encryption\Crypt'] = 'apps/files_encryption/lib/crypt.php';
OC::$CLASSPATH['OCA_Encryption\Hooks'] = 'apps/files_encryption/hooks/hooks.php';
OC::$CLASSPATH['OCA_Encryption\Util'] = 'apps/files_encryption/lib/util.php';
OC::$CLASSPATH['OCA_Encryption\Keymanager'] = 'apps/files_encryption/lib/keymanager.php';
OC::$CLASSPATH['OC_CryptStream'] = 'apps/files_encryption/lib/cryptstream.php';
OC::$CLASSPATH['OCA_Encryption\Proxy'] = 'apps/files_encryption/lib/proxy.php';

OC_FileProxy::register(new OCA_Encryption\Proxy());

OCP\Util::connectHook('OC_User','post_login','OCA_Encryption\Hooks','login');
OCP\Util::connectHook('OC_Webdav_Properties', 'update', 'OCA_Encryption\Hooks', 'updateKeyfile');

stream_wrapper_register('crypt','OC_CryptStream');

if(  !isset( $_SESSION['enckey'] ) && OCP\User::isLoggedIn() && OCA_Encryption\Crypt::mode() == 'server' ) {

	// Force the user to re-log in if the encryption key isn't unlocked (happens when a user is logged in before the encryption app is enabled)
	OCP\User::logout();
	
	header("Location: ".OC::$WEBROOT.'/');
	
	exit();

}

OCP\App::registerAdmin('files_encryption', 'settings');
OCP\App::registerPersonal('files_encryption','settings-personal');