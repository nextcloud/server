<?php

OC::$CLASSPATH['OC_Crypt'] = 'apps/files_encryption/lib/crypt.php';
OC::$CLASSPATH['OC_CryptStream'] = 'apps/files_encryption/lib/cryptstream.php';
OC::$CLASSPATH['OC_FileProxy_Encryption'] = 'apps/files_encryption/lib/proxy.php';

OC_FileProxy::register(new OC_FileProxy_Encryption());

OC_Hook::connect('OC_User','post_login','OC_Crypt','loginListener');

stream_wrapper_register('crypt','OC_CryptStream');
