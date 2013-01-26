<?php
/**
 * Copyright (c) 2012, Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
?>
<div id="oauth-request" class="guest-container">
	<p><strong><?php echo $_['consumer']['name'].'</strong> '.$_['message']; ?></p>
	<ul>
		<?php
		// Foreach requested scope
		foreach($_['requiredapps'] as $requiredapp){
			echo '<li>'.$requiredapp.'</li>';
		}
		?>
	</ul>
	<a href="<?php echo OC::$WEBROOT; ?>" id="back-home" class="button">Back to ownCloud</a>
</div>
