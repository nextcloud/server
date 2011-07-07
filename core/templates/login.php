<div id="login">
	<img src="<?php echo image_path('', 'owncloud-logo-medium-white.png'); ?>" alt="ownCloud" />
	<form action="index.php" method="post" id="login_form">
		<fieldset>
			<?php if($_['error']): ?>
				<?php echo $l->t( 'Login failed!' ); ?>
			<?php endif; ?>
			<input type="text" name="user" id="user" value="" autofocus />
			<input type="password" name="password" id="password" value="" />
			<input type="submit" value="Log in" />
		</fieldset>
	</form>
</div>

