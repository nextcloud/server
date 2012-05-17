<?php 
/**
 * Copyright (c) 2011 Marvin Thomas Rabe <mrabe@marvinrabe.de>
 * Copyright (c) 2011 Arthur Schiwon <blizzz@arthur-schiwon.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
?>
<input type="hidden" id="bookmarkFilterTag" value="<?php if(isset($_GET['tag'])) echo htmlentities($_GET['tag'],ENT_COMPAT,'utf-8'); ?>" />
<div id="controls">
	<input type="hidden" id="bookmark_add_id" value="0" />
	<input type="text" id="bookmark_add_url" placeholder="<?php echo $l->t('Address'); ?>" class="bookmarks_input" />
	<input type="text" id="bookmark_add_title" placeholder="<?php echo $l->t('Title'); ?>" class="bookmarks_input" />
	<input type="text" id="bookmark_add_tags" placeholder="<?php echo $l->t('Tags'); ?>" class="bookmarks_input" />
	<input type="submit" value="<?php echo $l->t('Save bookmark'); ?>" id="bookmark_add_submit" />
</div>
<div class="bookmarks_list">
</div>
<div id="firstrun" style="display: none;">
	<?php
		echo $l->t('You have no bookmarks');
		require_once(OC::$APPSROOT . '/apps/bookmarks/templates/bookmarklet.php');
		createBookmarklet(); 
	?>
</div>
