<form action="index.php" method="post">
	<fieldset>
		<p>
			<label for="password" class="infield"><?php echo $l->t('Password'); ?></label>
			<input type="password" name="password" id="password" value="" />
			<input type="submit" value="<?php echo $l->t('Submit'); ?>" />
		</p>
	</fieldset>
</form>