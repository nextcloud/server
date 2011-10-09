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
			<span class="bold"><?php echo $l->t('Bookmarklet:');?></span>&nbsp;<a class="bookmarks_addBml" href="javascript:var url = encodeURIComponent(location.href);window.open('<?php echo OC_Helper::linkTo('bookmarks', 'addBm.php', null, true); ?>?url='+url, 'owncloud-bookmarks');"><?php echo $l->t('Add page to ownCloud'); ?></a>
			<br/><em><?php echo $l->t('Drag this to your browser bookmarks and click it, when you want to bookmark a webpage.'); ?></em><br />
		</fieldset>
</form>
