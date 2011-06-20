<?php

require_once('../lib/base.php');
require( 'template.php' );
if( !OC_USER::isLoggedIn()){
    header( "Location: ".OC_HELPER::linkTo( "index.php" ));
    exit();
}

// Highlight navigation entry
OC_APP::setActiveNavigationEntry( "settings" );
OC_UTIL::addScript( "settings", "main" );
OC_UTIL::addStyle( "settings", "settings" );

// calculate the disc space
$used=OC_FILESYSTEM::filesize('/');
$free=OC_FILESYSTEM::free_space();
$total=$free+$used;
$relative=round(($used/$total)*100);

$lang=OC_PREFERENCES::getValue( $_SESSION['user_id'], 'core', 'lang', 'en' );
$languages=OC_L10N::findAvailableLanguages();
//put the current language in the front
unset($languages[array_search($lang,$languages)]);
array_unshift($languages,$lang);

// Return template
$tmpl = new OC_TEMPLATE( "settings", "index", "admin");
$tmpl->assign('usage',OC_HELPER::humanFileSize($used));
$tmpl->assign('total_space',OC_HELPER::humanFileSize($total));
$tmpl->assign('usage_relative',$relative);
$tmpl->assign('languages',$languages);
$tmpl->printPage();

?>
