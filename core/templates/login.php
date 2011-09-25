<form action="index.php" method="post">
	<fieldset>
		<?php if($_['error']): ?>
			<a href="lostpassword/index.php"><?php echo $l->t('Lost your password?'); ?></a>
		<?php endif; ?>
		<?php if(empty($_["username"])): ?>
			<input type="text" name="user" id="user" placeholder="Username" value="" autocomplete="off" required autofocus />
			<input type="password" name="password" id="password" placeholder="Password" value="" required />
			<input type="checkbox" name="remember_login" id="remember_login" /><label for="remember_login"><?php echo $l->t('remember'); ?></label>
		<?php else: ?>
		      <input type="text" name="user" id="user" placeholder="Username" value="<?php echo $_['username']; ?>" autocomplete="off" required >
		      <input type="password" name="password" id="password" placeholder="Password" value="" required autofocus />
		      <input type="checkbox" name="remember_login" id="remember_login" checked /><label for="remember_login"><?php echo $l->t('remember'); ?></label>
		<?php endif; ?>
		<input type="submit" id="submit" value="Log in" />
	</fieldset>
</form>
