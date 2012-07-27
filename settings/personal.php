<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once('../lib/base.php');
OC_Util::checkLoggedIn();

// Highlight navigation entry
OC_Util::addScript( 'settings', 'personal' );
OC_Util::addStyle( 'settings', 'settings' );
OC_Util::addScript( '3rdparty', 'chosen/chosen.jquery.min' );
OC_Util::addStyle( '3rdparty', 'chosen' );
OC_App::setActiveNavigationEntry( 'personal' );

// calculate the disc space
$rootInfo=OC_FileCache::get('');
$sharedInfo=OC_FileCache::get('/Shared');
if (!isset($sharedInfo['size'])) {
	$sharedSize = 0;
} else {
	$sharedSize = $sharedInfo['size'];
}
$used=$rootInfo['size']-$sharedSize;
$free=OC_Filesystem::free_space();
$total=$free+$used;
if($total==0) $total=1;  // prevent division by zero
$relative=round(($used/$total)*10000)/100;

$email=OC_Preferences::getValue(OC_User::getUser(), 'settings','email','');

$lang=OC_Preferences::getValue( OC_User::getUser(), 'core', 'lang', OC_L10N::findLanguage() );
$languageCodes=OC_L10N::findAvailableLanguages();
sort ($languageCodes);

//put the current language in the front
unset($languageCodes[array_search($lang,$languageCodes)]);
array_unshift($languageCodes,$lang);

$languageNames=include 'languageCodes.php';
$languages=array();
foreach($languageCodes as $lang){
	$l=OC_L10N::get('settings',$lang);
	if(substr($l->t('__language_name__'),0,1)!='_'){//first check if the language name is in the translation file
		$languages[]=array('code'=>$lang,'name'=>$l->t('__language_name__'));
	}elseif(isset($languageNames[$lang])){
		$languages[]=array('code'=>$lang,'name'=>$languageNames[$lang]);
	}else{//fallback to language code
		$languages[]=array('code'=>$lang,'name'=>$lang);
	}
}

// Return template
$tmpl = new OC_Template( 'settings', 'personal', 'user');
$tmpl->assign('usage',OC_Helper::humanFileSize($used));
$tmpl->assign('total_space',OC_Helper::humanFileSize($total));
$tmpl->assign('usage_relative',$relative);
$tmpl->assign('email',$email);
$tmpl->assign('languages',$languages);

$forms=OC_App::getForms('personal');
$tmpl->assign('forms',array());
foreach($forms as $form){
	$tmpl->append('forms',$form);
}
$tmpl->printPage();

?>