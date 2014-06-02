<form action="<?php print_unescaped($_['link']) ?>" id="reset-password" method="post">
	<fieldset>
		<p>
			<label for="password" class="infield"><?php p($l->t('New password')); ?></label>
			<input type="password" name="password" id="password" value="" required />
		</p>
		<input type="submit" id="submit" value="<?php p($l->t('Reset password')); ?>" />
	</fieldset>
</form>
<?php OCP\Util::addScript('core', 'lostpassword'); ?>
