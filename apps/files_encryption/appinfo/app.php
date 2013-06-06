<?php

OC::$CLASSPATH['OCA\Encryption\Crypt'] = 'files_encryption/lib/crypt.php';
OC::$CLASSPATH['OCA\Encryption\Hooks'] = 'files_encryption/hooks/hooks.php';
OC::$CLASSPATH['OCA\Encryption\Util'] = 'files_encryption/lib/util.php';
OC::$CLASSPATH['OCA\Encryption\Keymanager'] = 'files_encryption/lib/keymanager.php';
OC::$CLASSPATH['OCA\Encryption\Stream'] = 'files_encryption/lib/stream.php';
OC::$CLASSPATH['OCA\Encryption\Proxy'] = 'files_encryption/lib/proxy.php';
OC::$CLASSPATH['OCA\Encryption\Session'] = 'files_encryption/lib/session.php';
OC::$CLASSPATH['OCA\Encryption\Capabilities'] = 'files_encryption/lib/capabilities.php';
OC::$CLASSPATH['OCA\Encryption\Helper'] = 'files_encryption/lib/helper.php';

OC_FileProxy::register(new OCA\Encryption\Proxy());

// User related hooks
OCA\Encryption\Helper::registerUserHooks();

// Sharing related hooks
OCA\Encryption\Helper::registerShareHooks();

// Filesystem related hooks
OCA\Encryption\Helper::registerFilesystemHooks();

stream_wrapper_register('crypt', 'OCA\Encryption\Stream');

// check if we are logged in
if (OCP\User::isLoggedIn()) {

	// ensure filesystem is loaded
	if(!\OC\Files\Filesystem::$loaded) {
		\OC_Util::setupFS();
	}

	$view = new OC_FilesystemView('/');
	$session = new \OCA\Encryption\Session($view);

	// check if user has a private key
	if (
		!$session->getPrivateKey(\OCP\USER::getUser())
		&& OCA\Encryption\Crypt::mode() === 'server'
	) {

		// Force the user to log-in again if the encryption key isn't unlocked
		// (happens when a user is logged in before the encryption app is
		// enabled)
		OCP\User::logout();

		header("Location: " . OC::$WEBROOT . '/');

		exit();
	}
}

// Register settings scripts
OCP\App::registerAdmin('files_encryption', 'settings-admin');
OCP\App::registerPersonal('files_encryption', 'settings-personal');

