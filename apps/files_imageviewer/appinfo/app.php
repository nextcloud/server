<?php

if(OC_App::getCurrentApp()=='files'){
	OC_Util::addScript( 'files_imageviewer', 'lightbox' );
	OC_Util::addStyle( 'files_imageviewer', 'lightbox' );
}

?>
