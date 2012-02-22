<?php

function createBookmarklet() {
	$l = new OC_L10N('bookmarks');
	echo '<small>' . $l->t('Drag this to your browser bookmarks and click it, when you want to bookmark a webpage:') . '</small>'
	. '<a class="button" href="javascript:(function(){var a=window,b=document,c=encodeURIComponent,d=a.open(\'' . OC_Helper::linkToAbsolute('bookmarks', 'addBm.php') . '?output=popup&url=\'+c(b.location),\'bkmk_popup\',\'left=\'+((a.screenX||a.screenLeft)+10)+\',top=\'+((a.screenY||a.screenTop)+10)+\',height=510px,width=550px,resizable=1,alwaysRaised=1\');a.setTimeout(function(){d.focus()},300);})();">'
	. $l->t('Add page to ownCloud') . '</a>';
} 
