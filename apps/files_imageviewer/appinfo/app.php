<?php

if(OC_APP::getCurrentApp()=='files'){
	OC_UTIL::addScript( 'files_imageviewer', 'lightbox' );
	OC_UTIL::addStyle( 'files_imageviewer', 'lightbox' );
}

?>
