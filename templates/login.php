<div id="login">
	<img src="<?php echo image_path('', 'owncloud-logo-medium-white.png'); ?>" alt="ownCloud" />
	<form action="index.php" method="post" id="login_form">
		<fieldset>
			<?php if($_['error']): ?>
				Login failed!
			<?php endif; ?>
			<input type="text" name="user" id="user" value="" />
			<input type="password" name="password" id="password" value="" />
			<input type="submit" value="Log in" />
		</fieldset>
	</form>
</div>

