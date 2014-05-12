<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkAdminUser();

OC_Util::addStyle( "settings", "settings" );
OC_Util::addScript( "settings", "admin" );
OC_Util::addScript( "settings", "log" );
OC_App::setActiveNavigationEntry( "admin" );

$tmpl = new OC_Template( 'settings', 'admin', 'user');
$forms=OC_App::getForms('admin');
$htaccessworking=OC_Util::isHtaccessWorking();

$entries=OC_Log_Owncloud::getEntries(3);
$entriesremain = count(OC_Log_Owncloud::getEntries(4)) > 3;

// Should we display sendmail as an option?
$tmpl->assign('sendmail_is_available', (bool) findBinaryPath('sendmailsendmail'));

$tmpl->assign('loglevel', OC_Config::getValue( "loglevel", 2 ));
$tmpl->assign('mail_domain', OC_Config::getValue( "mail_domain", '' ));
$tmpl->assign('mail_from_address', OC_Config::getValue( "mail_from_address", '' ));
$tmpl->assign('mail_smtpmode', OC_Config::getValue( "mail_smtpmode", '' ));
$tmpl->assign('mail_smtpsecure', OC_Config::getValue( "mail_smtpsecure", '' ));
$tmpl->assign('mail_smtphost', OC_Config::getValue( "mail_smtphost", '' ));
$tmpl->assign('mail_smtpport', OC_Config::getValue( "mail_smtpport", '' ));
$tmpl->assign('mail_smtpauthtype', OC_Config::getValue( "mail_smtpauthtype", '' ));
$tmpl->assign('mail_smtpauth', OC_Config::getValue( "mail_smtpauth", false ));
$tmpl->assign('mail_smtpname', OC_Config::getValue( "mail_smtpname", '' ));
$tmpl->assign('mail_smtppassword', OC_Config::getValue( "mail_smtppassword", '' ));
$tmpl->assign('entries', $entries);
$tmpl->assign('entriesremain', $entriesremain);
$tmpl->assign('htaccessworking', $htaccessworking);
$tmpl->assign('internetconnectionworking', OC_Util::isInternetConnectionEnabled() ? OC_Util::isInternetConnectionWorking() : false);
$tmpl->assign('isLocaleWorking', OC_Util::isSetLocaleWorking());
$tmpl->assign('isWebDavWorking', OC_Util::isWebDAVWorking());
$tmpl->assign('has_fileinfo', OC_Util::fileInfoLoaded());
$tmpl->assign('old_php', OC_Util::isPHPoutdated());
$tmpl->assign('backgroundjobs_mode', OC_Appconfig::getValue('core', 'backgroundjobs_mode', 'ajax'));
$tmpl->assign('cron_log', OC_Config::getValue('cron_log', true));
$tmpl->assign('lastcron', OC_Appconfig::getValue('core', 'lastcron', false));
$tmpl->assign('shareAPIEnabled', OC_Appconfig::getValue('core', 'shareapi_enabled', 'yes'));
$tmpl->assign('shareDefaultExpireDateSet', OC_Appconfig::getValue('core', 'shareapi_default_expire_date', 'no'));
$tmpl->assign('shareExpireAfterNDays', OC_Appconfig::getValue('core', 'shareapi_expire_after_n_days', '7'));
$tmpl->assign('shareEnforceExpireDate', OC_Appconfig::getValue('core', 'shareapi_enforce_expire_date', 'no'));


// Check if connected using HTTPS
if (OC_Request::serverProtocol() === 'https') {
	$connectedHTTPS = true;
} else {
	$connectedHTTPS = false;
}
$tmpl->assign('isConnectedViaHTTPS', $connectedHTTPS);
$tmpl->assign('enforceHTTPSEnabled', OC_Config::getValue( "forcessl", false));

$tmpl->assign('allowLinks', OC_Appconfig::getValue('core', 'shareapi_allow_links', 'yes'));
$tmpl->assign('enforceLinkPassword', \OCP\Util::isPublicLinkPasswordRequired());
$tmpl->assign('allowPublicUpload', OC_Appconfig::getValue('core', 'shareapi_allow_public_upload', 'yes'));
$tmpl->assign('allowResharing', OC_Appconfig::getValue('core', 'shareapi_allow_resharing', 'yes'));
$tmpl->assign('allowMailNotification', OC_Appconfig::getValue('core', 'shareapi_allow_mail_notification', 'yes'));
$tmpl->assign('sharePolicy', OC_Appconfig::getValue('core', 'shareapi_share_policy', 'global'));
$tmpl->assign('forms', array());
foreach($forms as $form) {
	$tmpl->append('forms', $form);
}
$tmpl->printPage();

/**
 * Try to find a programm
 *
 * @param string $program
 * @return null|string
 */
function findBinaryPath($program) {
	exec('command -v ' . escapeshellarg($program) . ' 2> /dev/null', $output, $returnCode);
	if ($returnCode === 0 && count($output) > 0) {
		return escapeshellcmd($output[0]);
	}
	return null;
}
