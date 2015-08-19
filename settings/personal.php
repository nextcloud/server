<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Marvin Thomas Rabe <mrabe@marvinrabe.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Volkan Gezer <volkangezer@gmail.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

OC_Util::checkLoggedIn();

$defaults = new OC_Defaults(); // initialize themable default strings and urls
$certificateManager = \OC::$server->getCertificateManager();
$config = \OC::$server->getConfig();
$urlGenerator = \OC::$server->getURLGenerator();

// Highlight navigation entry
OC_Util::addScript( 'settings', 'personal' );
OC_Util::addStyle( 'settings', 'settings' );
\OC_Util::addVendorScript('strengthify/jquery.strengthify');
\OC_Util::addVendorStyle('strengthify/strengthify');
\OC_Util::addScript('files', 'jquery.fileupload');
if ($config->getSystemValue('enable_avatars', true) === true) {
	\OC_Util::addVendorScript('jcrop/js/jquery.Jcrop');
	\OC_Util::addVendorStyle('jcrop/css/jquery.Jcrop');
}

// Highlight navigation entry
OC_App::setActiveNavigationEntry( 'personal' );

$storageInfo=OC_Helper::getStorageInfo('/');

$email=$config->getUserValue(OC_User::getUser(), 'settings', 'email', '');

$userLang=$config->getUserValue( OC_User::getUser(), 'core', 'lang', OC_L10N::findLanguage() );
$languageCodes=OC_L10N::findAvailableLanguages();

// array of common languages
$commonlangcodes = array(
	'en', 'es', 'fr', 'de', 'de_DE', 'ja', 'ar', 'ru', 'nl', 'it', 'pt_BR', 'pt_PT', 'da', 'fi_FI', 'nb_NO', 'sv', 'tr', 'zh_CN', 'ko'
);

$languageNames=include 'languageCodes.php';
$languages=array();
$commonlanguages = array();
foreach($languageCodes as $lang) {
	$l = \OC::$server->getL10N('settings', $lang);
	if(substr($l->t('__language_name__'), 0, 1) !== '_') {//first check if the language name is in the translation file
		$ln=array('code'=>$lang, 'name'=> (string)$l->t('__language_name__'));
	}elseif(isset($languageNames[$lang])) {
		$ln=array('code'=>$lang, 'name'=>$languageNames[$lang]);
	}else{//fallback to language code
		$ln=array('code'=>$lang, 'name'=>$lang);
	}

	// put apropriate languages into apropriate arrays, to print them sorted
	// used language -> common languages -> divider -> other languages
	if ($lang === $userLang) {
		$userLang = $ln;
	} elseif (in_array($lang, $commonlangcodes)) {
		$commonlanguages[array_search($lang, $commonlangcodes)]=$ln;
	} else {
		$languages[]=$ln;
	}
}

ksort($commonlanguages);

// sort now by displayed language not the iso-code
usort( $languages, function ($a, $b) {
	return strcmp($a['name'], $b['name']);
});

//links to clients
$clients = array(
	'desktop' => $config->getSystemValue('customclient_desktop', $defaults->getSyncClientUrl()),
	'android' => $config->getSystemValue('customclient_android', $defaults->getAndroidClientUrl()),
	'ios'     => $config->getSystemValue('customclient_ios', $defaults->getiOSClientUrl())
);

// only show root certificate import if external storages are enabled
$enableCertImport = false;
$externalStorageEnabled = \OC::$server->getAppManager()->isEnabledForUser('files_external');
if ($externalStorageEnabled) {
	$enableCertImport = true;
}


// Return template
$tmpl = new OC_Template( 'settings', 'personal', 'user');
$tmpl->assign('usage', OC_Helper::humanFileSize($storageInfo['used']));
$tmpl->assign('total_space', OC_Helper::humanFileSize($storageInfo['total']));
$tmpl->assign('usage_relative', $storageInfo['relative']);
$tmpl->assign('clients', $clients);
$tmpl->assign('email', $email);
$tmpl->assign('languages', $languages);
$tmpl->assign('commonlanguages', $commonlanguages);
$tmpl->assign('activelanguage', $userLang);
$tmpl->assign('passwordChangeSupported', OC_User::canUserChangePassword(OC_User::getUser()));
$tmpl->assign('displayNameChangeSupported', OC_User::canUserChangeDisplayName(OC_User::getUser()));
$tmpl->assign('displayName', OC_User::getDisplayName());
$tmpl->assign('enableAvatars', $config->getSystemValue('enable_avatars', true));
$tmpl->assign('avatarChangeSupported', OC_User::canUserChangeAvatar(OC_User::getUser()));
$tmpl->assign('certs', $certificateManager->listCertificates());
$tmpl->assign('showCertificates', $enableCertImport);
$tmpl->assign('urlGenerator', $urlGenerator);

// Get array of group ids for this user
$groups = \OC::$server->getGroupManager()->getUserIdGroups(OC_User::getUser());
$groups2 = array_map(function($group) { return $group->getGID(); }, $groups);
sort($groups2);
$tmpl->assign('groups', $groups2);

// add hardcoded forms from the template
$l = OC_L10N::get('settings');
$formsAndMore = [];
$formsAndMore[]= ['anchor' => 'clientsbox', 'section-name' => $l->t('Sync clients')];
$formsAndMore[]= ['anchor' => 'passwordform', 'section-name' => $l->t('Personal info')];

$forms=OC_App::getForms('personal');

$formsMap = array_map(function($form){
	if (preg_match('%(<h2(?P<class>[^>]*)>.*?</h2>)%i', $form, $regs)) {
		$sectionName = str_replace('<h2'.$regs['class'].'>', '', $regs[0]);
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
if($enableCertImport) {
	$formsAndMore[]= array( 'anchor' => 'ssl-root-certificates', 'section-name' => $l->t('SSL root certificates') );
}



$tmpl->assign('forms', $formsAndMore);
$tmpl->printPage();
