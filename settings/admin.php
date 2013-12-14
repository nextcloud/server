<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkAdminUser();
OC_App::loadApps();

OC_Util::addStyle( "settings", "settings" );
OC_Util::addScript( "settings", "admin" );
OC_Util::addScript( "settings", "log" );
OC_App::setActiveNavigationEntry( "admin" );

$tmpl = new OC_Template( 'settings', 'admin', 'user');
$forms=OC_App::getForms('admin');
$htaccessworking=OC_Util::isHtAccessWorking();

$entries=OC_Log_Owncloud::getEntries(3);
$entriesremain = count(OC_Log_Owncloud::getEntries(4)) > 3;

$tmpl->assign('loglevel', OC_Config::getValue( "loglevel", 2 ));
$tmpl->assign('entries', $entries);
$tmpl->assign('entriesremain', $entriesremain);
$tmpl->assign('htaccessworking', $htaccessworking);
$tmpl->assign('internetconnectionworking', OC_Util::isInternetConnectionEnabled() ? OC_Util::isInternetConnectionWorking() : false);
$tmpl->assign('isLocaleWorking', OC_Util::isSetLocaleWorking());
$tmpl->assign('isWebDavWorking', OC_Util::isWebDAVWorking());
$tmpl->assign('has_fileinfo', OC_Util::fileInfoLoaded());
$tmpl->assign('old_php', OC_Util::isPHPoutdated());
$tmpl->assign('backgroundjobs_mode', OC_Appconfig::getValue('core', 'backgroundjobs_mode', 'ajax'));
$tmpl->assign('shareAPIEnabled', OC_Appconfig::getValue('core', 'shareapi_enabled', 'yes'));

// Check if connected using HTTPS
if (OC_Request::serverProtocol() === 'https') {
	$connectedHTTPS = true;
} else {
	$connectedHTTPS = false;
}
$tmpl->assign('isConnectedViaHTTPS', $connectedHTTPS);
$tmpl->assign('enforceHTTPSEnabled', OC_Config::getValue( "forcessl", false));

$tmpl->assign('allowLinks', OC_Appconfig::getValue('core', 'shareapi_allow_links', 'yes'));
$tmpl->assign('allowPublicUpload', OC_Appconfig::getValue('core', 'shareapi_allow_public_upload', 'yes'));
$tmpl->assign('allowResharing', OC_Appconfig::getValue('core', 'shareapi_allow_resharing', 'yes'));
$tmpl->assign('allowMailNotification', OC_Appconfig::getValue('core', 'shareapi_allow_mail_notification', 'yes'));
$tmpl->assign('sharePolicy', OC_Appconfig::getValue('core', 'shareapi_share_policy', 'global'));
$tmpl->assign('forms', array());
foreach($forms as $form) {
	$tmpl->append('forms', $form);
}
$tmpl->printPage();
