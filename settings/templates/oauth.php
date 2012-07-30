<?php
/**
 * Copyright (c) 2012, Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
?>
<div id="oauth-request" class="guest-container">
	<p><strong><?php echo $_['consumer']['name']; ?></strong> is requesting permission to read, write, modify and delete data from the following apps:</p>
	<ul>
		<?php
		// Foreach requested scope
		foreach($_['consumer']['scopes'] as $app){
			echo '<li>'.$app.'</li>';
		}
		?>
	</ul>
	<button>Allow</button>
	<button>Disallow</button>
</div>
