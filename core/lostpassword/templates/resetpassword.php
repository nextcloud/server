<form action="<?php echo 'resetpassword.php?'.$_SERVER['QUERY_STRING']; ?>" method="post">
	<fieldset>
		<?php if($_['success']): ?>
			<h1><?php echo $l->t('Your password was reset'); ?></h1>
			<p><a href="<?php echo OC::$WEBROOT ?>/"><?php echo $l->t('To login page'); ?></a></p>
		<?php else: ?>
			<p class="infield">
				<label for="password" class="infield"><?php echo $l->t( 'New password' ); ?></label>
				<input type="password" name="password" id="password" value="" required />
			</p>
			<input type="submit" id="submit" value="<?php echo $l->t('Reset password'); ?>" />
		<?php endif; ?>
	</fieldset>
</form>
