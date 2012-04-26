<form action="index.php" method="post">
	<fieldset>
		<?php echo $l->t('You will receive a link to reset your password via Email.'); ?>
		<?php if ($_['requested']): ?>
			<?php echo $l->t('Requested'); ?>
		<?php else: ?>
			<?php if ($_['error']): ?>
				<?php echo $l->t('Login failed!'); ?>
			<?php endif; ?>
			<p class="infield">
				<label for="user" class="infield"><?php echo $l->t( 'Username' ); ?></label>
				<input type="text" name="user" id="user" value="" autocomplete="off" required autofocus />
				<input type="hidden" name="sectoken" id="sectoken" value="<?php echo($_['sectoken']); ?>"  />
			</p>
			<input type="submit" id="submit" value="<?php echo $l->t('Request reset'); ?>" />
		<?php endif; ?>
	</fieldset>
</form>
