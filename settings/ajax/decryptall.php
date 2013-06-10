<?php

$status = OC_App::isEnabled('files_encryption');
OC_App::enable('files_encryption');

OCA\Encryption\Crypt::decryptAll();

if ($status === false) {
	OC_App::disable('files_encryption');
}


\OCP\JSON::success(array('data' => array('message' => 'looks good')));

