<div class="personal-settings-setting-box personal-settings-group-box section where-is-your-data">
	<h3>
		<?php p($l->t('Where is your data?')); ?>
		<a
			target="_blank"
			rel="noreferrer noopener"
			class="icon-info"
			title=""
			href="https://nextcloud.com/yourdata/"
			data-original-title="Open documentation"></a>
	</h3>
	<?php if (empty($_['dataLocation']) === false): ?>
	<div class="personal-info icon-address">
		<p>
			<?php echo $l->t('Your data is located in <b>%s</b>.', [$_['dataLocation']]); ?>
		</p>
	</div>
	<?php endif; ?>

	<?php if (empty($_['provider']) === false): ?>
	<div class="personal-info icon-home">
		<p>
			<?php
				if (empty($_['providerLink']) === false) {
					echo $l->t('Your provider is %s%s%s.', [
						'<a href="' . $_['providerLink'] . '" target="_blank" title="" rel="noreferrer noopener">',
						$_['provider'],
						'</a>'
					]);
				} else {
					echo $l->t('Your provider is %s.', [$_['provider']]);
				}
			?>
			<?php
				if (empty($_['providerPrivacyLink']) === false) {
					echo $l->t('Read the %sprivacy policy%s now.', [
						'<a href="' . $_['providerPrivacyLink'] . '" target="_blank" title="" rel="noreferrer noopener">',
						'</a>'
					]);
				}
			?>
		</p>
	</div>
	<?php endif; ?>

	<?php if ($_['encryptionEnabled'] === true): ?>
		<div class="personal-info icon-password">
			<p>
				<?php echo $l->t(
					'Your files are encrypted with %sserver side encryption%s.',
					[
						'<a href="https://nextcloud.com/blog/encryption-in-nextcloud/" target="_blank" title="" rel="noreferrer noopener">',
						'</a>'
					]
				); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if (empty($_['adminName']) === false): ?>
	<div class="personal-info icon-user-admin">
		<p>
			<?php echo $l->t(
				'%s%s%s is your admin. If you have any issues, %scontact them%s.',
				[
					'<a href="mailto:' . $_['adminMail'] . '" target="_blank" title="" rel="noreferrer noopener">',
					$_['adminName'],
					'</a>',
					'<a href="mailto:' . $_['adminMail'] . '" target="_blank" title="" rel="noreferrer noopener">',
					'</a>'
				]
			); ?>
		</p>
	</div>
	<?php endif; ?>
</div>
