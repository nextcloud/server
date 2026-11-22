<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2011-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// @codeCoverageIgnoreStart
if (!isset($_)) {//standalone  page is not supported anymore - redirect to /
	require_once '../../lib/base.php';

	$urlGenerator = \OCP\Server::get(\OCP\IURLGenerator::class);
	header('Location: ' . $urlGenerator->getAbsoluteURL('/'));
	exit;
}
// @codeCoverageIgnoreEnd
?>
<div class="body-login-container update">
	<div class="icon-big icon-password"></div>
	<h2><?php p($l->t('Access forbidden')); ?></h2>
	<p class="hint">
		<?php if (isset($_['message'])): ?>
			<?php p($_['message']); ?>
		<?php else: ?>
			<?php p($l->t('You are not allowed to access this page.')); ?>
		<?php endif; ?>
	</p>
	<p><a class="button primary" href="<?php p(\OCP\Server::get(\OCP\IURLGenerator::class)->linkTo('', 'index.php')) ?>">
		<?php p($l->t('Back to %s', [$theme->getName()])); ?>
	</a></p>
</div>
