<?php
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
\OCP\Util::addStyle('files_sharing', 'accept-share');
?>

<div class="guest-box accept-share">
	<form action="" method="post">
		<h2><?php p($l->t('%1$s shared %2$s with you', [$_['sharerDisplayName'], $_['filename']])); ?></h2>
		<p><?php p($l->t('Do you want to accept this share?')); ?></p>
		<div class="buttons">
			<input type="submit" class="primary" value="<?php p($l->t('Accept')); ?>">
		</div>
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>">
	</form>
</div>
