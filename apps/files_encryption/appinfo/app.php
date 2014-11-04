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

// Exceptions
OC::$CLASSPATH['OCA\Encryption\Exceptions\MultiKeyEncryptException'] = 'files_encryption/lib/exceptions.php';
OC::$CLASSPATH['OCA\Encryption\Exceptions\MultiKeyDecryptException'] = 'files_encryption/lib/exceptions.php';
OC::$CLASSPATH['OCA\Encryption\Exceptions\EncryptionException'] = 'files_encryption/lib/exceptions.php';

\OCP\Util::addTranslations('files_encryption');
\OCP\Util::addscript('files_encryption', 'encryption');
\OCP\Util::addscript('files_encryption', 'detect-migration');

if (!OC_Config::getValue('maintenance', false)) {
	OC_FileProxy::register(new OCA\Encryption\Proxy());

	// User related hooks
	OCA\Encryption\Helper::registerUserHooks();

	// Sharing related hooks
	OCA\Encryption\Helper::registerShareHooks();

	// Filesystem related hooks
	OCA\Encryption\Helper::registerFilesystemHooks();

	// App manager related hooks
	OCA\Encryption\Helper::registerAppHooks();

	if(!in_array('crypt', stream_get_wrappers())) {
		stream_wrapper_register('crypt', 'OCA\Encryption\Stream');
	}
} else {
	// logout user if we are in maintenance to force re-login
	OCP\User::logout();
}

// Register settings scripts
OCP\App::registerAdmin('files_encryption', 'settings-admin');
OCP\App::registerPersonal('files_encryption', 'settings-personal');
