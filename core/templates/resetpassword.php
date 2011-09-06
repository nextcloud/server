<form action="<?php echo 'index.php?'.$_SERVER['QUERY_STRING']; ?>" method="post">
	<fieldset>
		<?php if($_['success']): ?>
			<?php echo $l->t('Your password was reset'); ?>
		<?php else: ?>
			<input type="password" name="password" id="password" placeholder="<?php echo $l->t('New password'); ?>" value="" required />
			<input type="submit" id="submit" value="<?php echo $l->t('Reset password'); ?>" />
		<?php endif; ?>
	</fieldset>
</form>
