<form action="index.php" method="post">
	<fieldset>
		<?php /*if($_['error']): ?>
			<a href="index.php?lostpassword"><?php echo $l->t('Lost your password?'); ?></a>
		<?php endif;*/ ?>
		<?php if(empty($_['username'])): ?>
			<input type="text" name="user" id="user" placeholder="<?php echo $l->t( 'Username' ); ?>" value="" autocomplete="off" required autofocus />
			<input type="password" name="password" id="password" placeholder="<?php echo $l->t( 'Password' ); ?>" value="" required />
			<input type="checkbox" name="remember_login" value="1" id="remember_login" /><label for="remember_login"><?php echo $l->t('remember'); ?></label>
		<?php else: ?>
		      <input type="text" name="user" id="user" placeholder="<?php echo $l->t( 'Username' ); ?>" value="<?php echo $_['username']; ?>" autocomplete="off" required >
		      <input type="password" name="password" id="password" placeholder="<?php echo $l->t( 'Password' ); ?>" value="" required autofocus />
		      <input type="checkbox" name="remember_login" value="1" id="remember_login" checked /><label for="remember_login"><?php echo $l->t('remember'); ?></label>
		<?php endif; ?>
		<input type="submit" id="submit" class="login" value="<?php echo $l->t( 'Log in' ); ?>" />
	</fieldset>
</form>
