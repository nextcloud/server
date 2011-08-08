<?php

require_once('../lib/base.php');
if( !OC_User::isLoggedIn()){
    header( "Location: ".OC_Helper::linkTo( "index.php" ));
    exit();
}

// Highlight navigation entry
OC_App::setActiveNavigationEntry( "settings" );
OC_Util::addScript( "settings", "main" );
OC_Util::addStyle( "settings", "settings" );

// calculate the disc space
$used=OC_Filesystem::filesize('/');
$free=OC_Filesystem::free_space();
$total=$free+$used;
$relative=round(($used/$total)*100);

$lang=OC_Preferences::getValue( OC_User::getUser(), 'core', 'lang', 'en' );
$languages=OC_L10N::findAvailableLanguages();
//put the current language in the front
unset($languages[array_search($lang,$languages)]);
array_unshift($languages,$lang);

// Return template
$tmpl = new OC_Template( "settings", "index", "user");
$tmpl->assign('usage',OC_Helper::humanFileSize($used));
$tmpl->assign('total_space',OC_Helper::humanFileSize($total));
$tmpl->assign('usage_relative',$relative);
$tmpl->assign('languages',$languages);
$tmpl->assign('hasopenid',OC_App::isEnabled( 'user_openid' ));
if(OC_App::isEnabled( 'user_openid' )){
	$identity=OC_Preferences::getValue(OC_User::getUser(),'user_openid','identity','');
	$tmpl->assign('identity',$identity);
}
$tmpl->printPage();

?>
