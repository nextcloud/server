<form action="<?php echo $_['URL']; ?>" method="post">
	<fieldset>
		<p class="infield">
			<label for="password" class="infield"><?php echo $l->t('Password'); ?></label>
			<input type="password" name="password" id="password" value="" autofocus />
			<input type="submit" value="<?php echo $l->t('Submit'); ?>" />
		</p>
	</fieldset>
</form>