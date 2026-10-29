<?php
declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

?>
<div class="body-login-container update two-factor">
	<h2 class="two-factor-header"><?php p($l->t('Set up two-factor authentication')) ?></h2>
	<?php p($l->t('Enhanced security is enforced for your account. Choose which provider to set up:')) ?>
	<ul class="two-factor-providers">
	<?php foreach ($_['providers'] as $provider): ?>
		<li>
			<a class="two-factor-provider"
			   href="<?php p(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('core.TwoFactorChallenge.setupProvider',
			   	[
			   		'providerId' => $provider->getId(),
			   		'redirect_url' => $_['redirect_url'],
			   	]
			   )) ?>">
				<?php
				if ($provider instanceof \OCP\Authentication\TwoFactorAuth\IProvidesIcons) {
					$icon = $provider->getLightIcon();
				} else {
					$icon = image_path('core', 'actions/password-white.svg');
				}
		?>
				<span class="two-factor-provider-icon"><img src="<?php p($icon) ?>" alt="" /></span>
				<div>
					<h3><?php p($provider->getDisplayName()) ?></h3>
					<p><?php p($provider->getDescription()) ?></p>
				</div>
			</a>
		</li>
	<?php endforeach; ?>
	</ul>
	<div class="two-factor-actions">
		<a id="cancel-login" href="<?php print_unescaped($_['logout_url']); ?>">
			<?php p($l->t('Cancel login')) ?>
		</a>
	</div>
</div>
