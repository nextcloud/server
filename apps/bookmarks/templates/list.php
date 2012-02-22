<?php 
/**
 * Copyright (c) 2011 Marvin Thomas Rabe <mrabe@marvinrabe.de>
 * Copyright (c) 2011 Arthur Schiwon <blizzz@arthur-schiwon.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
?>
<input type="hidden" id="bookmarkFilterTag" value="<?php if(isset($_GET['tag'])) echo htmlentities($_GET['tag']); ?>" />
<div id="controls">
	<input type="button" class="bookmarks_addBtn" value="<?php echo $l->t('Add bookmark'); ?>"/>
</div>
<div class="bookmarks_add">
	<input type="hidden" id="bookmark_add_id" value="0" />
	<p><label class="bookmarks_label"><?php echo $l->t('Address'); ?></label><input type="text" id="bookmark_add_url" class="bookmarks_input" /></p>
	<p><label class="bookmarks_label"><?php echo $l->t('Title'); ?></label><input type="text" id="bookmark_add_title" class="bookmarks_input" />
       <img class="loading_meta" src="<?php echo OC_Helper::imagePath('core', 'loading.gif'); ?>" /></p>
	<p><label class="bookmarks_label"><?php echo $l->t('Tags'); ?></label><input type="text" id="bookmark_add_tags" class="bookmarks_input" /></p>
	<p><label class="bookmarks_label"> </label><label class="bookmarks_hint"><?php echo $l->t('Hint: Use space to separate tags.'); ?></label></p>
	<p><label class="bookmarks_label"></label><input type="submit" value="<?php echo $l->t('Add bookmark'); ?>" id="bookmark_add_submit" /></p>
</div>
<div class="bookmarks_list">
</div>
<div id="firstrun">
	<?php echo $l->t('You have no bookmarks'); ?>
	<small><?php echo $l->t('Drag this to your browser bookmarks and click it, when you want to bookmark a webpage.'); ?></small>
	<div id="selections">
		<a class="button" href='javascript:(function(){var a=window,b=document,c=encodeURIComponent,d=a.open("<?php echo OC_Helper::linkToAbsolute('bookmarks', 'addBm.php') ?>?output=popup&url="+c(b.location)+"&title="+c(b.title),"bkmk_popup","left="+((a.screenX||a.screenLeft)+10)+",top="+((a.screenY||a.screenTop)+10)+",height=510px,width=550px,resizable=1,alwaysRaised=1");a.setTimeout(function(){d.focus()},300)})();'><?php echo $l->t('Add page to ownCloud'); ?></a>
	</div>
</div>
