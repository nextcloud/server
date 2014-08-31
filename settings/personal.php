<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkLoggedIn();

$defaults = new OC_Defaults(); // initialize themable default strings and urls

// Highlight navigation entry
OC_Util::addScript( 'settings', 'personal' );
OC_Util::addStyle( 'settings', 'settings' );
OC_Util::addScript( '3rdparty', 'strengthify/jquery.strengthify' );
OC_Util::addStyle( '3rdparty', 'strengthify/strengthify' );
OC_Util::addScript( '3rdparty', 'chosen/chosen.jquery.min' );
OC_Util::addStyle( '3rdparty', 'chosen/chosen' );
\OC_Util::addScript('files', 'jquery.fileupload');
if (\OC_Config::getValue('enable_avatars', true) === true) {
	\OC_Util::addScript('3rdparty/Jcrop', 'jquery.Jcrop.min');
	\OC_Util::addStyle('3rdparty/Jcrop', 'jquery.Jcrop.min');
}

// Highlight navigation entry
OC_App::setActiveNavigationEntry( 'personal' );

$storageInfo=OC_Helper::getStorageInfo('/');

$email=OC_Preferences::getValue(OC_User::getUser(), 'settings', 'email', '');

$userLang=OC_Preferences::getValue( OC_User::getUser(), 'core', 'lang', OC_L10N::findLanguage() );
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
	'desktop' => OC_Config::getValue('customclient_desktop', $defaults->getSyncClientUrl()),
	'android' => OC_Config::getValue('customclient_android', $defaults->getAndroidClientUrl()),
	'ios'     => OC_Config::getValue('customclient_ios', $defaults->getiOSClientUrl())
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
$tmpl->assign('enableAvatars', \OC_Config::getValue('enable_avatars', true));
$tmpl->assign('avatarChangeSupported', OC_User::canUserChangeAvatar(OC_User::getUser()));

$forms=OC_App::getForms('personal');
$tmpl->assign('forms', array());
foreach($forms as $form) {
	$tmpl->append('forms', $form);
}
$tmpl->printPage();
