<?php
/** @var array $_ */
/** @var $l OC_L10N */
style('lostpassword', 'resetpassword');
script('core', 'lostpassword');
?>

<form action="<?php print_unescaped($_['link']) ?>" id="reset-password" method="post">
	<fieldset>
		<p>
			<label for="password" class="infield"><?php p($l->t('New password')); ?></label>
			<input type="password" name="password" id="password" value="" placeholder="<?php p($l->t('New Password')); ?>" required />
			<img class="svg" id="password-icon" src="<?php print_unescaped(image_path('', 'actions/password.svg')); ?>" alt=""/>
		</p>
		<input type="submit" id="submit" value="<?php p($l->t('Reset password')); ?>" />
	</fieldset>
</form>
