<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkLoggedIn();

$defaults = new OC_Defaults(); // initialize themable default strings and urls
$certificateManager = \OC::$server->getCertificateManager();
$config = \OC::$server->getConfig();

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

//check if encryption was enabled in the past
$filesStillEncrypted = OC_Util::encryptedFiles();
$backupKeysExists = OC_Util::backupKeysExists();
$enableDecryptAll = $filesStillEncrypted || $backupKeysExists;

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
$tmpl->assign('enableDecryptAll' , $enableDecryptAll);
$tmpl->assign('backupKeysExists' , $backupKeysExists);
$tmpl->assign('filesStillEncrypted' , $filesStillEncrypted);
$tmpl->assign('enableAvatars', $config->getSystemValue('enable_avatars', true));
$tmpl->assign('avatarChangeSupported', OC_User::canUserChangeAvatar(OC_User::getUser()));
$tmpl->assign('certs', $certificateManager->listCertificates());

// add hardcoded forms from the template
$l = OC_L10N::get('settings');
$formsAndMore = array();
$formsAndMore[]= array( 'anchor' => 'passwordform', 'section-name' => $l->t('Personal Info') );

$forms=OC_App::getForms('personal');

$formsMap = array_map(function($form){
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
$formsAndMore[]= array( 'anchor' => 'ssl-root-certificates', 'section-name' => $l->t('SSL root certificates') );
if($enableDecryptAll) {
	$formsAndMore[]= array( 'anchor' => 'encryption', 'section-name' => $l->t('Encryption') );
}

$tmpl->assign('forms', $formsAndMore);
$tmpl->printPage();
