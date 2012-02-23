<?php
/**
 * Copyright (c) 2011 Marvin Thomas Rabe <mrabe@marvinrabe.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
?>
<form id="bookmarks">
		<fieldset class="personalblock">
			<span class="bold"><?php echo $l->t('Bookmarklet <br />');?></span>
			<?php
			    require_once('bookmarklet.php');
			    createBookmarklet(); 
			?>
		</fieldset>
</form>
