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
$l = OC_L10N::get('core');

// Get the config
$apps_paths = array();
foreach(OC_App::getEnabledApps() as $app) {
	$apps_paths[$app] = OC_App::getAppWebPath($app);
}

$array = array(
	"oc_debug" => (defined('DEBUG') && DEBUG) ? 'true' : 'false',
	"oc_webroot" => "\"".OC::$WEBROOT."\"",
	"oc_appswebroots" =>  str_replace('\\/', '/', json_encode($apps_paths)), // Ugly unescape slashes waiting for better solution
	"oc_current_user" =>  "\"".OC_User::getUser(). "\"",
	"oc_requesttoken" =>  "\"".OC_Util::callRegister(). "\"",
	"datepickerFormatDate" => json_encode($l->l('jsdate', 'jsdate')),
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
	"firstDay" => json_encode($l->l('firstday', 'firstday')) ,
	);

// Echo it
foreach ($array as  $setting => $value) {
	echo("var ". $setting ."=".$value.";\n");
}
