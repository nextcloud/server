<?php 
/**
 * Copyright (c) 2011 Marvin Thomas Rabe <m.rabe@echtzeitraum.de>
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
	<?php echo $l->t('You have no bookmarks'); ?>
</div>