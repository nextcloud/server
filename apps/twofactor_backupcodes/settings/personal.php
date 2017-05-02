<?php

use OCA\TwoFactorBackupCodes\Provider\BackupCodesProvider;
use OCP\Template;

// @codeCoverageIgnoreStart

/* @var $provider BackupCodesProvider */
$provider = OC::$server->query(BackupCodesProvider::class);
$user = OC::$server->getUserSession()->getUser();

if ($provider->isActive($user)) {
	$tmpl = new Template('twofactor_backupcodes', 'personal');
	return $tmpl->fetchPage();
} else {
	return "";
}

// @codeCoverageIgnoreEnd
