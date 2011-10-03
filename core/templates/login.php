<form action="index.php" method="post">
	<fieldset>
		<?php if($_['error']): ?>
			<a href="./lostpassword/"><?php echo $l->t('Lost your password?'); ?></a>
		<?php endif; ?>
		<p class="infield">
			<label for="user" class="infield"><?php echo $l->t( 'Username' ); ?></label>
			<input type="text" name="user" id="user" value="<?php echo !empty($_POST['user'])?$_POST['user'].'"':'" autofocus'; ?> autocomplete="off" required />
		</p>
		<p class="infield">
			<label for="password" class="infield"><?php echo $l->t( 'Password' ); ?></label>
			<input type="password" name="password" id="password" value="" required <?php echo !empty($_POST['user'])?'autofocus':''; ?> />
		</p>
		<input type="checkbox" name="remember_login" value="1" id="remember_login" /><label for="remember_login"><?php echo $l->t('remember'); ?></label>
		<input type="submit" id="submit" class="login" value="<?php echo $l->t( 'Log in' ); ?>" />
	</fieldset>
</form>
