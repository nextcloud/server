<?php
/**
 * Copyright (c) 2012, Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
?>
<div id="oauth-request" class="guest-container">
	<p><strong><?php print_unescaped(OC_Util::sanitizeHTML($_['consumer']['name']).'</strong> '.OC_Util::sanitizeHTML($_['message'])); ?></p>
	<ul>
		<?php
		// Foreach requested scope
		foreach($_['requiredapps'] as $requiredapp){
			print_unescaped('<li>'.OC_Util::sanitizeHTML($requiredapp).'</li>');
		}
		?>
	</ul>
	<a href="<?php print_unescaped(OC::$WEBROOT); ?>" id="back-home" class="button">Back to ownCloud</a>
</div>
