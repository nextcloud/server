<div id="login">
	<img src="<?php echo image_path('', 'owncloud-logo-medium-white.png'); ?>" alt="ownCloud" />
	<form action="index.php" method="post" id="login_form">
		<fieldset>
			<?php if($_['error']): ?>
				<?php echo $l->t( 'Login failed!' ); ?>
			<?php endif; ?>
			<?php if(empty($_["username"])){?>
			<input type="text" name="user" id="user" value="" autofocus />
			<input type="checkbox" name="remember_login"/> <?php echo $l->t('Remember login'); ?>
			<input type="password" name="password" id="password" value="" />
			<?php }else{ ?>
			<input type="text" name="user" id="user" value="<?php echo $_['username']; ?>">
			<input type="checkbox" name="remember_login" checked /> <?php echo $l->t('Remember login'); ?>
			<input type="password" name="password" id="password" value="" autofocus />
			<?php } ?>
			<input type="submit" value="Log in" />
		</fieldset>
	</form>
</div>

