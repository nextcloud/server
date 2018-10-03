<div class="warning">
	<h2 class="two-factor-header"><?php p($l->t('Two-factor authentication')) ?></h2>
	<p><?php p($l->t('Enhanced security is enabled for your account. Choose a second factor for authentication:')) ?></p>
	<?php if ($_['providerMissing']): ?>
	<p>
		<strong><?php p($l->t('Could not load at least one of your enabled two-factor auth methods. Please contact your admin.')) ?></strong>
	</p>
	<?php endif; ?>
	<p>
		<ul>
			<?php if (empty($_['providers'])): ?>
			<p>
				<?php if (is_null($_['backupProvider'])): ?>
				<strong><?php p($l->t('Two-factor authentication is enforced but has not been configured on your account. Contact your admin for assistance.')) ?></strong>
				<?php else: ?>
				<strong><?php p($l->t('Two-factor authentication is enforced but has not been configured on your account. Use one of your backup codes to log in or contact your admin for assistance.')) ?></strong>
				<?php endif; ?>
			</p>
			<?php else: ?>
			<?php foreach ($_['providers'] as $provider): ?>
				<li>
					<a class="two-factor-provider"
					   href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.TwoFactorChallenge.showChallenge',
										[
											'challengeProviderId' => $provider->getId(),
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
						<img src="<?php p($icon) ?>" alt="" />
						<div>
							<h3><?php p($provider->getDisplayName()) ?></h3>
							<p><?php p($provider->getDescription()) ?></p>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
	<?php endif ?>
			<?php if (!is_null($_['backupProvider'])): ?>
				<li>
					<a class="two-factor-provider two-factor-secondary" href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.TwoFactorChallenge.showChallenge',
						[
							'challengeProviderId' => $_['backupProvider']->getId(),
							'redirect_url' => $_['redirect_url'],
						]
					)) ?>">
						<div>
							<p><?php p($l->t('Use backup code')) ?></p>
						</div>
					</a>
				</li>
			<?php endif; ?>
			<li>
				<a class="two-factor-provider two-factor-secondary" href="<?php print_unescaped($_['logout_url']); ?>">
					<div>
						<p><?php p($l->t('Cancel log in')) ?></p>
					</div>
				</a>
			</li>
		</ul>
	</p>
</div>
