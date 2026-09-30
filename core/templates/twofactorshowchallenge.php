<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/** @var \OCP\IL10N $l */
/** @var array $_ */
/** @var boolean $error */
$error = $_['error'];
/* @var $error_message string */
$error_message = $_['error_message'];
/* @var $provider OCP\Authentication\TwoFactorAuth\IProvider */
$provider = $_['provider'];
/* @var $template string */
$template = $_['template'];
?>

<div class="body-login-container update two-factor">
	<h2 class="two-factor-header"><?php p($provider->getDisplayName()); ?></h2>
	<?php if ($error): ?>
			<?php if ($error_message): ?>
				<p><strong><?php p($error_message); ?></strong></p>
			<?php else: ?>
				<p><strong><?php p($l->t('Error while validating your second factor')); ?></strong></p>
			<?php endif; ?>
	<?php endif; ?>
	<?php print_unescaped($template); ?>
	<div class="two-factor-actions">
		<?php if ($_['hasOtherProviders']): ?>
		<a class="two-factor-action-switch" href="<?php p(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('core.TwoFactorChallenge.selectChallenge',
			[
				'redirect_url' => $_['redirect_url'],
			]
		)) ?>">
			<?php p($l->t('Use another method')) ?>
		</a>
		<?php endif; ?>
		<?php if (!is_null($_['backupProvider'])): ?>
		<a class="two-factor-action-backup" href="<?php p(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('core.TwoFactorChallenge.showChallenge',
			[
				'challengeProviderId' => $_['backupProvider']->getId(),
				'redirect_url' => $_['redirect_url'],
			]
		)) ?>">
			<?php p($l->t('Use backup code')) ?>
		</a>
		<?php endif; ?>
		<a id="cancel-login" href="<?php print_unescaped($_['logout_url']); ?>">
			<?php p($l->t('Cancel login')) ?>
		</a>
	</div>
</div>
