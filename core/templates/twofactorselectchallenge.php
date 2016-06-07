<fieldset class="warning">
		<legend><strong><?php p($l->t('Two-step verification')) ?></strong></legend>
		<p><?php p($l->t('Enhanced security has been enabled for your account. Please authenticate using a second factor.')) ?></p>
</fieldset>
<fieldset class="warning">
<ul>
<?php foreach ($_['providers'] as $provider): ?>
	<li>
		<a class="two-factor-provider"
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
</fieldset>
<a class="two-factor-cancel" <?php print_unescaped($_['logout_attribute']); ?>><?php p($l->t('Cancel login')) ?></a>
