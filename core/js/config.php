<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Guillaume AMAT <guillaume.amat@informatique-libre.com>
 * @author Hasso Tepper <hasso@zone.ee>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Owen Winkler <a_github@midnightcircus.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Vincent Chan <plus.vincchan@gmail.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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

// Set the content type to Javascript
header("Content-type: text/javascript");

// Disallow caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Enable l10n support
$l = \OC::$server->getL10N('core');

// Enable OC_Defaults support
$defaults = new OC_Defaults();

// Get the config
$apps_paths = array();
foreach(OC_App::getEnabledApps() as $app) {
	$apps_paths[$app] = OC_App::getAppWebPath($app);
}

$config = \OC::$server->getConfig();
$value = $config->getAppValue('core', 'shareapi_default_expire_date', 'no');
$defaultExpireDateEnabled = ($value === 'yes') ? true :false;
$defaultExpireDate = $enforceDefaultExpireDate = null;
if ($defaultExpireDateEnabled) {
	$defaultExpireDate = (int) $config->getAppValue('core', 'shareapi_expire_after_n_days', '7');
	$value = $config->getAppValue('core', 'shareapi_enforce_expire_date', 'no');
	$enforceDefaultExpireDate = ($value === 'yes') ? true : false;
}
$outgoingServer2serverShareEnabled = $config->getAppValue('files_sharing', 'outgoing_server2server_share_enabled', 'yes') === 'yes';

$countOfDataLocation = 0;

$dataLocation = str_replace(OC::$SERVERROOT .'/', '', $config->getSystemValue('datadirectory', ''), $countOfDataLocation);
if($countOfDataLocation !== 1 || !OC_User::isAdminUser(OC_User::getUser())){
	$dataLocation = false;
}

$array = array(
	"oc_debug" => $config->getSystemValue('debug', false) ? 'true' : 'false',
	"oc_isadmin" => OC_User::isAdminUser(OC_User::getUser()) ? 'true' : 'false',
	"oc_dataURL" => is_string($dataLocation) ? "\"".$dataLocation."\"" : 'false',
	"oc_webroot" => "\"".OC::$WEBROOT."\"",
	"oc_appswebroots" =>  str_replace('\\/', '/', json_encode($apps_paths)), // Ugly unescape slashes waiting for better solution
	"datepickerFormatDate" => json_encode($l->l('jsdate', null)),
	"dayNames" =>  json_encode(
		array(
			(string)$l->t('Sunday'),
			(string)$l->t('Monday'),
			(string)$l->t('Tuesday'),
			(string)$l->t('Wednesday'),
			(string)$l->t('Thursday'),
			(string)$l->t('Friday'),
			(string)$l->t('Saturday')
		)
	),
	"dayNamesShort" =>  json_encode(
		array(
			(string)$l->t('Sun.'),
			(string)$l->t('Mon.'),
			(string)$l->t('Tue.'),
			(string)$l->t('Wed.'),
			(string)$l->t('Thu.'),
			(string)$l->t('Fri.'),
			(string)$l->t('Sat.')
		)
	),
	"dayNamesMin" =>  json_encode(
		array(
			(string)$l->t('Su'),
			(string)$l->t('Mo'),
			(string)$l->t('Tu'),
			(string)$l->t('We'),
			(string)$l->t('Th'),
			(string)$l->t('Fr'),
			(string)$l->t('Sa')
		)
	),
	"monthNames" => json_encode(
		array(
			(string)$l->t('January'),
			(string)$l->t('February'),
			(string)$l->t('March'),
			(string)$l->t('April'),
			(string)$l->t('May'),
			(string)$l->t('June'),
			(string)$l->t('July'),
			(string)$l->t('August'),
			(string)$l->t('September'),
			(string)$l->t('October'),
			(string)$l->t('November'),
			(string)$l->t('December')
		)
	),
	"monthNamesShort" => json_encode(
		array(
			(string)$l->t('Jan.'),
			(string)$l->t('Feb.'),
			(string)$l->t('Mar.'),
			(string)$l->t('Apr.'),
			(string)$l->t('May.'),
			(string)$l->t('Jun.'),
			(string)$l->t('Jul.'),
			(string)$l->t('Aug.'),
			(string)$l->t('Sep.'),
			(string)$l->t('Oct.'),
			(string)$l->t('Nov.'),
			(string)$l->t('Dec.')
		)
	),
	"firstDay" => json_encode($l->l('firstday', null)) ,
	"oc_config" => json_encode(
		array(
			'session_lifetime'	=> min(\OCP\Config::getSystemValue('session_lifetime', OC::$server->getIniWrapper()->getNumeric('session.gc_maxlifetime')), OC::$server->getIniWrapper()->getNumeric('session.gc_maxlifetime')),
			'session_keepalive'	=> \OCP\Config::getSystemValue('session_keepalive', true),
			'version'			=> implode('.', \OCP\Util::getVersion()),
			'versionstring'		=> OC_Util::getVersionString(),
			'enable_avatars'	=> \OC::$server->getConfig()->getSystemValue('enable_avatars', true) === true,
			'lost_password_link'=> \OC::$server->getConfig()->getSystemValue('lost_password_link', null),
			'modRewriteWorking'	=> (getenv('front_controller_active') === 'true'),
		)
	),
	"oc_appconfig" => json_encode(
			array("core" => array(
				'defaultExpireDateEnabled' => $defaultExpireDateEnabled,
				'defaultExpireDate' => $defaultExpireDate,
				'defaultExpireDateEnforced' => $enforceDefaultExpireDate,
				'enforcePasswordForPublicLink' => \OCP\Util::isPublicLinkPasswordRequired(),
				'sharingDisabledForUser' => \OCP\Util::isSharingDisabledForUser(),
				'resharingAllowed' => \OCP\Share::isResharingAllowed(),
				'remoteShareAllowed' => $outgoingServer2serverShareEnabled,
				'federatedCloudShareDoc' => \OC::$server->getURLGenerator()->linkToDocs('user-sharing-federated'),
				'allowGroupSharing' => \OC::$server->getShareManager()->allowGroupSharing()
				)
			)
	),
	"oc_defaults" => json_encode(
		array(
			'entity' => $defaults->getEntity(),
			'name' => $defaults->getName(),
			'title' => $defaults->getTitle(),
			'baseUrl' => $defaults->getBaseUrl(),
			'syncClientUrl' => $defaults->getSyncClientUrl(),
			'docBaseUrl' => $defaults->getDocBaseUrl(),
			'docPlaceholderUrl' => $defaults->buildDocLinkToKey('PLACEHOLDER'),
			'slogan' => $defaults->getSlogan(),
			'logoClaim' => $defaults->getLogoClaim(),
			'shortFooter' => $defaults->getShortFooter(),
			'longFooter' => $defaults->getLongFooter(),
			'folder' => OC_Util::getTheme(),
		)
	)
);

// Allow hooks to modify the output values
OC_Hook::emit('\OCP\Config', 'js', array('array' => &$array));

// Echo it
foreach ($array as  $setting => $value) {
	echo("var ". $setting ."=".$value.";\n");
}
