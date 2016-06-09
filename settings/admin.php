<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Martin Mattel <martin.mattel@diemattels.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

use OC\Lock\NoopLockingProvider;

OC_Util::checkAdminUser();
\OC::$server->getNavigationManager()->setActiveEntry("admin");

$template = new OC_Template('settings', 'admin', 'user');
$l = \OC::$server->getL10N('settings');

OC_Util::addScript('settings', 'certificates');
OC_Util::addScript('files', 'jquery.fileupload');

\OC::$server->getEventDispatcher()->dispatch('OC\Settings\Admin::loadAdditionalScripts');

$showLog = (\OC::$server->getConfig()->getSystemValue('log_type', 'owncloud') === 'owncloud');
$numEntriesToLoad = 3;
$entries = \OC\Log\Owncloud::getEntries($numEntriesToLoad + 1);
$entriesRemaining = count($entries) > $numEntriesToLoad;
$entries = array_slice($entries, 0, $numEntriesToLoad);
$logFilePath = \OC\Log\Owncloud::getLogFilePath();
$doesLogFileExist = file_exists($logFilePath);
$logFileSize = 0;
if($doesLogFileExist) {
	$logFileSize = filesize($logFilePath);
}

$config = \OC::$server->getConfig();
$appConfig = \OC::$server->getAppConfig();
$request = \OC::$server->getRequest();
$certificateManager = \OC::$server->getCertificateManager(null);
$urlGenerator = \OC::$server->getURLGenerator();

// Should we display sendmail as an option?
$template->assign('sendmail_is_available', (bool) \OC_Helper::findBinaryPath('sendmail'));

$template->assign('loglevel', $config->getSystemValue("loglevel", 2));
$template->assign('mail_domain', $config->getSystemValue("mail_domain", ''));
$template->assign('mail_from_address', $config->getSystemValue("mail_from_address", ''));
$template->assign('mail_smtpmode', $config->getSystemValue("mail_smtpmode", ''));
$template->assign('mail_smtpsecure', $config->getSystemValue("mail_smtpsecure", ''));
$template->assign('mail_smtphost', $config->getSystemValue("mail_smtphost", ''));
$template->assign('mail_smtpport', $config->getSystemValue("mail_smtpport", ''));
$template->assign('mail_smtpauthtype', $config->getSystemValue("mail_smtpauthtype", ''));
$template->assign('mail_smtpauth', $config->getSystemValue("mail_smtpauth", false));
$template->assign('mail_smtpname', $config->getSystemValue("mail_smtpname", ''));
$template->assign('mail_smtppassword', $config->getSystemValue("mail_smtppassword", ''));
$template->assign('entries', $entries);
$template->assign('entriesremain', $entriesRemaining);
$template->assign('logFileSize', $logFileSize);
$template->assign('doesLogFileExist', $doesLogFileExist);
$template->assign('showLog', $showLog);
$template->assign('readOnlyConfigEnabled', OC_Helper::isReadOnlyConfigEnabled());
$template->assign('isLocaleWorking', OC_Util::isSetLocaleWorking());
$template->assign('isAnnotationsWorking', OC_Util::isAnnotationsWorking());
$template->assign('checkForWorkingWellKnownSetup', $config->getSystemValue('check_for_working_wellknown_setup', true));
$template->assign('has_fileinfo', OC_Util::fileInfoLoaded());
$template->assign('backgroundjobs_mode', $appConfig->getValue('core', 'backgroundjobs_mode', 'ajax'));
$template->assign('cron_log', $config->getSystemValue('cron_log', true));
$template->assign('lastcron', $appConfig->getValue('core', 'lastcron', false));
$template->assign('shareAPIEnabled', $appConfig->getValue('core', 'shareapi_enabled', 'yes'));
$template->assign('shareDefaultExpireDateSet', $appConfig->getValue('core', 'shareapi_default_expire_date', 'no'));
$template->assign('shareExpireAfterNDays', $appConfig->getValue('core', 'shareapi_expire_after_n_days', '7'));
$template->assign('shareEnforceExpireDate', $appConfig->getValue('core', 'shareapi_enforce_expire_date', 'no'));
$excludeGroups = $appConfig->getValue('core', 'shareapi_exclude_groups', 'no') === 'yes' ? true : false;
$template->assign('shareExcludeGroups', $excludeGroups);
$excludedGroupsList = $appConfig->getValue('core', 'shareapi_exclude_groups_list', '');
$excludedGroupsList = json_decode($excludedGroupsList);
$template->assign('shareExcludedGroupsList', !is_null($excludedGroupsList) ? implode('|', $excludedGroupsList) : '');
$template->assign('encryptionEnabled', \OC::$server->getEncryptionManager()->isEnabled());
$backends = \OC::$server->getUserManager()->getBackends();
$externalBackends = (count($backends) > 1) ? true : false;
$template->assign('encryptionReady', \OC::$server->getEncryptionManager()->isReady());
$template->assign('externalBackendsEnabled', $externalBackends);

/** @var \Doctrine\DBAL\Connection $connection */
$connection = \OC::$server->getDatabaseConnection();
try {
	if ($connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform) {
		$template->assign('invalidTransactionIsolationLevel', false);
	} else {
		$template->assign('invalidTransactionIsolationLevel', $connection->getTransactionIsolation() !== \Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED);
	}
} catch (\Doctrine\DBAL\DBALException $e) {
	// ignore
	$template->assign('invalidTransactionIsolationLevel', false);
}

$encryptionModules = \OC::$server->getEncryptionManager()->getEncryptionModules();
$defaultEncryptionModuleId = \OC::$server->getEncryptionManager()->getDefaultEncryptionModuleId();

$encModulues = array();
foreach ($encryptionModules as $module) {
	$encModulues[$module['id']]['displayName'] = $module['displayName'];
	$encModulues[$module['id']]['default'] = false;
	if ($module['id'] === $defaultEncryptionModuleId) {
		$encModulues[$module['id']]['default'] = true;
	}
}
$template->assign('encryptionModules', $encModulues);

// If the current web root is non-empty but the web root from the config is,
// and system cron is used, the URL generator fails to build valid URLs.
$shouldSuggestOverwriteCliUrl = $config->getAppValue('core', 'backgroundjobs_mode', 'ajax') === 'cron' &&
	\OC::$WEBROOT && \OC::$WEBROOT !== '/' &&
	!$config->getSystemValue('overwrite.cli.url', '');
$suggestedOverwriteCliUrl = ($shouldSuggestOverwriteCliUrl) ? \OC::$WEBROOT : '';
$template->assign('suggestedOverwriteCliUrl', $suggestedOverwriteCliUrl);

$template->assign('allowLinks', $appConfig->getValue('core', 'shareapi_allow_links', 'yes'));
$template->assign('enforceLinkPassword', \OCP\Util::isPublicLinkPasswordRequired());
$template->assign('allowPublicUpload', $appConfig->getValue('core', 'shareapi_allow_public_upload', 'yes'));
$template->assign('allowResharing', $appConfig->getValue('core', 'shareapi_allow_resharing', 'yes'));
$template->assign('allowPublicMailNotification', $appConfig->getValue('core', 'shareapi_allow_public_notification', 'no'));
$template->assign('allowMailNotification', $appConfig->getValue('core', 'shareapi_allow_mail_notification', 'no'));
$template->assign('allowShareDialogUserEnumeration', $appConfig->getValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes'));
$template->assign('onlyShareWithGroupMembers', \OC\Share\Share::shareWithGroupMembersOnly());
$template->assign('allowGroupSharing', $appConfig->getValue('core', 'shareapi_allow_group_sharing', 'yes'));
$databaseOverload = (strpos(\OCP\Config::getSystemValue('dbtype'), 'sqlite') !== false);
$template->assign('databaseOverload', $databaseOverload);
$template->assign('cronErrors', $appConfig->getValue('core', 'cronErrors'));

// warn if php is not setup properly to get system variables with getenv
$path = getenv('PATH');
$template->assign('getenvServerNotWorking', empty($path));

// warn if Windows is used
$template->assign('WindowsWarning', OC_Util::runningOnWindows());

// warn if outdated version of a memcache module is used
$caches = [
	'apcu'	=> ['name' => $l->t('APCu'), 'version' => '4.0.6'],
	'redis'	=> ['name' => $l->t('Redis'), 'version' => '2.2.5'],
];

$outdatedCaches = [];
foreach ($caches as $php_module => $data) {
	$isOutdated = extension_loaded($php_module) && version_compare(phpversion($php_module), $data['version'], '<');
	if ($isOutdated) {
		$outdatedCaches[$php_module] = $data;
	}
}
$template->assign('OutdatedCacheWarning', $outdatedCaches);

// add hardcoded forms from the template
$forms = OC_App::getForms('admin');

if ($config->getSystemValue('enable_certificate_management', false)) {
	$certificatesTemplate = new OC_Template('settings', 'certificates');
	$certificatesTemplate->assign('type', 'admin');
	$certificatesTemplate->assign('uploadRoute', 'settings.Certificate.addSystemRootCertificate');
	$certificatesTemplate->assign('certs', $certificateManager->listCertificates());
	$certificatesTemplate->assign('urlGenerator', $urlGenerator);
	$forms[] = $certificatesTemplate->fetchPage();
}

$formsAndMore = array();
if ($request->getServerProtocol()  !== 'https' || !OC_Util::isAnnotationsWorking() ||
	$suggestedOverwriteCliUrl || !OC_Util::isSetLocaleWorking()  ||
	!OC_Util::fileInfoLoaded() || $databaseOverload
) {
	$formsAndMore[] = array('anchor' => 'security-warning', 'section-name' => $l->t('Security & setup warnings'));
}
$formsAndMore[] = array('anchor' => 'shareAPI', 'section-name' => $l->t('Sharing'));
$formsAndMore[] = ['anchor' => 'encryptionAPI', 'section-name' => $l->t('Server-side encryption')];

// Prioritize fileSharingSettings and files_external and move updater to the version
$fileSharingSettings = $filesExternal = $updaterAppPanel = $ocDefaultEncryptionModulePanel = '';
foreach ($forms as $index => $form) {
	if (strpos($form, 'id="fileSharingSettings"')) {
		$fileSharingSettings = $form;
		unset($forms[$index]);
		continue;
	}
	if (strpos($form, 'id="files_external"')) {
		$filesExternal = $form;
		unset($forms[$index]);
		continue;
	}
	if (strpos($form, 'class="updater-admin"')) {
		$updaterAppPanel = $form;
		unset($forms[$index]);
		continue;
	}
	if (strpos($form, 'id="ocDefaultEncryptionModule"')) {
		$ocDefaultEncryptionModulePanel = $form;
		unset($forms[$index]);
		continue;
	}
}
if ($filesExternal) {
	$formsAndMore[] = array('anchor' => 'files_external', 'section-name' => $l->t('External Storage'));
}

$template->assign('fileSharingSettings', $fileSharingSettings);
$template->assign('filesExternal', $filesExternal);
$template->assign('updaterAppPanel', $updaterAppPanel);
$template->assign('ocDefaultEncryptionModulePanel', $ocDefaultEncryptionModulePanel);
$lockingProvider = \OC::$server->getLockingProvider();
if ($lockingProvider instanceof NoopLockingProvider) {
	$template->assign('fileLockingType', 'none');
} else if ($lockingProvider instanceof \OC\Lock\DBLockingProvider) {
	$template->assign('fileLockingType', 'db');
} else {
	$template->assign('fileLockingType', 'cache');
}

$formsMap = array_map(function ($form) {
	if (preg_match('%(<h2(?P<class>[^>]*)>.*?</h2>)%i', $form, $regs)) {
		$sectionName = str_replace('<h2'.$regs['class'].'>', '', $regs[0]);
		$sectionName = str_replace('</h2>', '', $sectionName);
		$anchor = strtolower($sectionName);
		$anchor = str_replace(' ', '-', $anchor);

		return array(
			'anchor' => $anchor,
			'section-name' => $sectionName,
			'form' => $form
		);
	}
	return array(
		'form' => $form
	);
}, $forms);

$formsAndMore = array_merge($formsAndMore, $formsMap);

// add bottom hardcoded forms from the template
$formsAndMore[] = ['anchor' => 'backgroundjobs', 'section-name' => $l->t('Cron')];
$formsAndMore[] = ['anchor' => 'mail_general_settings', 'section-name' => $l->t('Email server')];
$formsAndMore[] = ['anchor' => 'log-section', 'section-name' => $l->t('Log')];
$formsAndMore[] = ['anchor' => 'admin-tips', 'section-name' => $l->t('Tips & tricks')];
if ($updaterAppPanel) {
	$formsAndMore[] = ['anchor' => 'updater', 'section-name' => $l->t('Updates')];
}

$template->assign('forms', $formsAndMore);

$template->printPage();

$util = new \OC_Util();
$util->createHtaccessTestFile(\OC::$server->getConfig());

