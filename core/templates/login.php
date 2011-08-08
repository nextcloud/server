<div id="login">
	<header>
		<img src="<?php echo image_path('', 'owncloud-logo-medium-white.png'); ?>" alt="ownCloud" />
	</header>
	<form action="index.php" method="post">
		<fieldset>
			<?php if($_['error']): ?>
				<?php echo $l->t( 'Login failed!' ); ?>
			<?php endif; ?>
			<?php if(empty($_["username"])){?>
			<input type="text" name="user" id="user" placeholder="Username" value="" autocomplete="off" required autofocus />
			<input type="password" name="password" id="password" placeholder="Password" value="" required />
			<input type="checkbox" name="remember_login" id="remember_login" /> <?php echo $l->t('remember'); ?>
			<?php }else{ ?>
			<input type="text" name="user" id="user" placeholder="Username" value="<?php echo $_['username']; ?>" autocomplete="off" required >
			<input type="password" name="password" id="password" placeholder="Password" value="" required autofocus />
			<input type="checkbox" name="remember_login" id="remember_login" checked /> <?php echo $l->t('remember'); ?>
			<?php } ?>
			<input type="submit" id="submit" value="Log in" />
		</fieldset>
	</form>
</div>

