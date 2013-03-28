<form action="<?php p($_['URL']); ?>" method="post">
	<fieldset>
		<p class="infield">
			<label for="password" class="infield"><?php p($l->t('Password')); ?></label>
			<input type="password" name="password" id="password" placeholder="" value="" autofocus />
			<input type="submit" value="<?php p($l->t('Submit')); ?>" />
		</p>
	</fieldset>
</form>
