<form action="index.php?lostpassword" method="post">
	<fieldset>
		<?php echo $l->t('You will receive a link to reset your password via Email.'); ?>
		<?php if ($_['requested']): ?>
			<?php echo $l->t('Requested'); ?>
		<?php else: ?>
			<?php if ($_['error']): ?>
				<?php echo $l->t('Login failed!'); ?>
			<?php endif; ?>
			<input type="text" name="user" id="user" placeholder="<?php echo $l->t('Username or Email'); ?>" value="" autocomplete="off" required autofocus />
			<input type="submit" id="submit" value="<?php echo $l->t('Request reset'); ?>" />
		<?php endif; ?>
	</fieldset>
</form>