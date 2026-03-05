<?php
declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

?>

<div id="two-factor-auth" class="section">
	<h2><?php p($l->t('Two-Factor Authentication'));?></h2>
	<a target="_blank" rel="noreferrer noopener" class="icon-info"
	   title="<?php p($l->t('Open documentation'));?>"
	   href="<?php p(link_to_docs('user-2fa')); ?>"></a>
	<p class="settings-hint"><?php p($l->t('Use a second factor besides your password to increase security for your account.'));?></p>
	<p class="settings-hint"><?php p($l->t('If you use third party applications to connect to Nextcloud, please make sure to create and configure an app password for each before enabling second factor authentication.'));?></p>
	<ul>
	<?php foreach ($_['twoFactorProviderData']['providers'] as $data) { ?>
		<li>
			<?php

			/** @var \OCP\Authentication\TwoFactorAuth\IProvidesPersonalSettings $provider */
			$provider = $data['provider'];
		//Handle 2FA provider icons and theme
		if ($provider instanceof \OCP\Authentication\TwoFactorAuth\IProvidesIcons) {
			$icon = $provider->getDarkIcon();
			//fallback icon if the 2factor provider doesn't provide an icon.
		} else {
			$icon = image_path('core', 'actions/password.svg');
		}
		/** @var \OCP\Authentication\TwoFactorAuth\IPersonalProviderSettings $settings */
		$settings = $data['settings'];
		?>
			<h3>
				<img class="two-factor-provider-settings-icon" src="<?php p($icon) ?>" alt="">
				<?php p($provider->getDisplayName()) ?>
			</h3>
			<?php print_unescaped($settings->getBody()->fetchPage()) ?>
		</li>
	<?php } ?>
	</ul>
</div>
