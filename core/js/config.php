<?php
/**
 * Copyright (c) 2013 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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

$value = \OCP\Config::getAppValue('core', 'shareapi_default_expire_date', 'no');
$defaultExpireDateEnabled = ($value === 'yes') ? true :false;
$defaultExpireDate = $enforceDefaultExpireDate = null;
if ($defaultExpireDateEnabled) {
	$defaultExpireDate = (int)\OCP\Config::getAppValue('core', 'shareapi_expire_after_n_days', '7');
	$value = \OCP\Config::getAppValue('core', 'shareapi_enforce_expire_date', 'no');
	$enforceDefaultExpireDate = ($value === 'yes') ? true : false;
}

$array = array(
	"oc_debug" => (defined('DEBUG') && DEBUG) ? 'true' : 'false',
	"oc_isadmin" => OC_User::isAdminUser(OC_User::getUser()) ? 'true' : 'false',
	"oc_webroot" => "\"".OC::$WEBROOT."\"",
	"oc_appswebroots" =>  str_replace('\\/', '/', json_encode($apps_paths)), // Ugly unescape slashes waiting for better solution
	"datepickerFormatDate" => json_encode($l->getDateFormat()),
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
	"firstDay" => json_encode($l->getFirstWeekDay()) ,
	"oc_config" => json_encode(
		array(
			'session_lifetime'	=> min(\OCP\Config::getSystemValue('session_lifetime', ini_get('session.gc_maxlifetime')), ini_get('session.gc_maxlifetime')),
			'session_keepalive'	=> \OCP\Config::getSystemValue('session_keepalive', true),
			'version'			=> implode('.', OC_Util::getVersion()),
			'versionstring'		=> OC_Util::getVersionString(),
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
			'slogan' => $defaults->getSlogan(),
			'logoClaim' => $defaults->getLogoClaim(),
			'shortFooter' => $defaults->getShortFooter(),
			'longFooter' => $defaults->getLongFooter()
		)
	)
);

// Allow hooks to modify the output values
OC_Hook::emit('\OCP\Config', 'js', array('array' => &$array));

// Echo it
foreach ($array as  $setting => $value) {
	echo("var ". $setting ."=".$value.";\n");
}
