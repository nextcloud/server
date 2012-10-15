<form method="post">
	<fieldset>
		<ul>
			<li class="errors">
				<?php echo $l->t('Security Warning!'); ?><br>
				<small><?php echo $l->t("Please verify your password. <br/>For security reasons you may be occasionally asked to enter your password again. "); ?></small>
			</li>
		</ul>
		<p class="infield">
			<input type="text"  value="<?php echo $_['username']; ?>" disabled="disabled" />
		</p>
		<p class="infield">
			<label for="password" class="infield"><?php echo $l->t( 'Password' ); ?></label>
			<input type="password" name="password" id="password" value="" required />
		</p>
		<input type="submit" id="submit" class="login" value="<?php echo $l->t( 'Verify' ); ?>" />
	</fieldset>
</form>
