<?php
/**
 * Copyright (c) 2011 Marvin Thomas Rabe <m.rabe@echtzeitraum.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
?>
<form id="bookmarks">
		<fieldset class="personalblock">
			<span class="bold"><?php echo $l->t('Bookmarklet:');?></span>&nbsp;<a class="bookmarks_addBml" href='javascript:(function(){var a=window,b=document,c=encodeURIComponent,d=a.open("<?php echo OC_Helper::linkToAbsolute('bookmarks', 'addBm.php') ?>?output=popup&url="+c(b.location)+"&title="+c(b.title),"bkmk_popup","left="+((a.screenX||a.screenLeft)+10)+",top="+((a.screenY||a.screenTop)+10)+",height=510px,width=550px,resizable=1,alwaysRaised=1");a.setTimeout(function(){d.focus()},300)})();'><?php echo $l->t('Add page to ownCloud'); ?></a>
			<br/><em><?php echo $l->t('Drag this to your browser bookmarks and click it, when you want to bookmark a webpage.'); ?></em><br />
		</fieldset>
</form>
