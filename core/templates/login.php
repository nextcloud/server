<div id="login">
	<header>
		<div class='header'>
			<img src="<?php echo image_path('', 'owncloud-logo-medium-white.png'); ?>" alt="ownCloud" />
		</div>
	</header>
	<form action="index.php" method="post" id="login_form">
		<fieldset>
			<?php if($_['error']): ?>
				<?php echo $l->t( 'Login failed!' ); ?>
			<?php endif; ?>
			<?php if(empty($_["username"])){?>
			<input type="text" name="user" id="user" placeholder="Username" value="" required autofocus />
			<input type="password" name="password" id="password" placeholder="Password" value="" required />
			<input type="checkbox" name="remember_login"/> <?php echo $l->t('Remember login'); ?>
			<?php }else{ ?>
			<input type="text" name="user" id="user" placeholder="Username" value="<?php echo $_['username']; ?>" required >
			<input type="password" name="password" id="password" value="" placeholder="Password" required autofocus />
			<input type="checkbox" name="remember_login" checked /> <?php echo $l->t('Remember login'); ?>
			<?php } ?>
			<input type="submit" value="Log in" />
		</fieldset>
	</form>
</div>

