<div class="warning">
	<h2 class="two-factor-header"><?php p($l->t('Two-factor authentication')) ?></h2>
	<p><?php p($l->t('Enhanced security is enabled for your account. Please authenticate using a second factor.')) ?></p>
	<p>
		<ul>
			<?php foreach ($_['providers'] as $provider): ?>
				<li>
					<a class="button two-factor-provider"
					   href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.TwoFactorChallenge.showChallenge',
										[
											'challengeProviderId' => $provider->getId(),
											'redirect_url' => $_['redirect_url'],
										]
									)) ?>">
						<?php p($provider->getDescription()) ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</p>
	<p class="two-factor-link">
		<a class="button" href="<?php print_unescaped($_['logout_url']); ?>"><?php p($l->t('Cancel log in')) ?></a>
		<?php if (!is_null($_['backupProvider'])): ?>
		<a class="button" href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.TwoFactorChallenge.showChallenge',
												[
													'challengeProviderId' => $_['backupProvider']->getId(),
													'redirect_url' => $_['redirect_url'],
												]
											)) ?>"><?php p($l->t('Use backup code')) ?></a>
		<?php endif; ?>
	</p>
</div>
