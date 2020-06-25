<?php
style('twofactor_backupcodes', 'style');
?>

<img class="two-factor-icon" src="<?php p(image_path('core', 'actions/more-white.svg')) ?>" alt="" />

<p><?php p($l->t('Use one of the backup codes you saved when setting up two-factor authentication.')) ?></p>

<form method="POST" class="challenge-form">
	<input type="text" class="challenge" name="challenge" required="required" autofocus autocomplete="off" autocapitalize="off" placeholder="<?php p($l->t('Backup code')) ?>">
	<button class="two-factor-submit primary" type="submit">
		<?php p($l->t('Submit')); ?>
	</button>
</form>
