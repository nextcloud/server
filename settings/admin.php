<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkAdminUser();
OC_App::setActiveNavigationEntry("admin");

$template = new OC_Template('settings', 'admin', 'user');

$showLog = (\OC::$server->getConfig()->getSystemValue('log_type', 'owncloud') === 'owncloud');
$numEntriesToLoad = 3;
$entries = OC_Log_Owncloud::getEntries($numEntriesToLoad + 1);
$entriesRemaining = count($entries) > $numEntriesToLoad;
$entries = array_slice($entries, 0, $numEntriesToLoad);
$logFilePath = OC_Log_Owncloud::getLogFilePath();
$doesLogFileExist = file_exists($logFilePath);
$logFileSize = filesize($logFilePath);
$config = \OC::$server->getConfig();
$appConfig = \OC::$server->getAppConfig();

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
$template->assign('isPhpCharSetUtf8', OC_Util::isPhpCharSetUtf8());
$template->assign('isAnnotationsWorking', OC_Util::isAnnotationsWorking());
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
$excludedGroupsList = explode(',', $excludedGroupsList); // FIXME: this should be JSON!
$template->assign('shareExcludedGroupsList', implode('|', $excludedGroupsList));

// Check if connected using HTTPS
$template->assign('isConnectedViaHTTPS', OC_Request::serverProtocol() === 'https');
$template->assign('enforceHTTPSEnabled', $config->getSystemValue('forcessl', false));
$template->assign('forceSSLforSubdomainsEnabled', $config->getSystemValue('forceSSLforSubdomains', false));

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
$template->assign('onlyShareWithGroupMembers', \OC\Share\Share::shareWithGroupMembersOnly());
$databaseOverload = (strpos(\OCP\Config::getSystemValue('dbtype'), 'sqlite') !== false);
$template->assign('databaseOverload', $databaseOverload);

// warn if Windows is used
$template->assign('WindowsWarning', OC_Util::runningOnWindows());

// warn if outdated version of APCu is used
$template->assign('ApcuOutdatedWarning',
	extension_loaded('apcu') && version_compare(phpversion('apc'), '4.0.6') === -1);

// add hardcoded forms from the template
$forms = OC_App::getForms('admin');
$l = OC_L10N::get('settings');
$formsAndMore = array();
if (OC_Request::serverProtocol() !== 'https' || !OC_Util::isAnnotationsWorking() ||
	$suggestedOverwriteCliUrl || !OC_Util::isSetLocaleWorking() || !OC_Util::isPhpCharSetUtf8() ||
	!OC_Util::fileInfoLoaded() || $databaseOverload
) {
	$formsAndMore[] = array('anchor' => 'security-warning', 'section-name' => $l->t('Security & Setup Warnings'));
}

$formsMap = array_map(function ($form) {
	if (preg_match('%(<h2[^>]*>.*?</h2>)%i', $form, $regs)) {
		$sectionName = str_replace('<h2>', '', $regs[0]);
		$sectionName = str_replace('</h2>', '', $sectionName);
		$anchor = strtolower($sectionName);
		$anchor = str_replace(' ', '-', $anchor);

		return array(
			'anchor' => 'goto-' . $anchor,
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
$formsAndMore[] = array('anchor' => 'backgroundjobs', 'section-name' => $l->t('Cron'));
$formsAndMore[] = array('anchor' => 'shareAPI', 'section-name' => $l->t('Sharing'));
$formsAndMore[] = array('anchor' => 'security', 'section-name' => $l->t('Security'));
$formsAndMore[] = array('anchor' => 'mail_general_settings', 'section-name' => $l->t('Email Server'));
$formsAndMore[] = array('anchor' => 'log-section', 'section-name' => $l->t('Log'));

$template->assign('forms', $formsAndMore);

$template->printPage();
