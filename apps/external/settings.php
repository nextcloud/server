<?php

OC_Util::checkAdminUser();

OC_Util::addScript( "external", "admin" );

$tmpl = new OC_Template( 'external', 'settings');

	$tmpl->assign('s1name',OC_Appconfig::getValue( "external","site1name", '' ));
	$tmpl->assign('s2name',OC_Appconfig::getValue( "external","site2name", '' ));
	$tmpl->assign('s3name',OC_Appconfig::getValue( "external","site3name", '' ));
	$tmpl->assign('s4name',OC_Appconfig::getValue( "external","site4name", '' ));
	$tmpl->assign('s5name',OC_Appconfig::getValue( "external","site5name", '' ));

	$tmpl->assign('s1url',OC_Appconfig::getValue( "external","site1url", '' ));
	$tmpl->assign('s2url',OC_Appconfig::getValue( "external","site2url", '' ));
	$tmpl->assign('s3url',OC_Appconfig::getValue( "external","site3url", '' ));
	$tmpl->assign('s4url',OC_Appconfig::getValue( "external","site4url", '' ));
	$tmpl->assign('s5url',OC_Appconfig::getValue( "external","site5url", '' ));

return $tmpl->fetchPage();
?>
