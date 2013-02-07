<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}</style><![endif]-->
<form method="post">
	<fieldset>
	<?php if (!empty($_['redirect_url'])) {
		echo '<input type="hidden" name="redirect_url" value="' . $_['redirect_url'] . '" />';
	} ?>
		<ul>
			<?php if (isset($_['invalidcookie']) && ($_['invalidcookie'])): ?>
			<li class="errors">
				<?php echo $l->t('Automatic logon rejected!'); ?><br>
				<small><?php echo $l->t('If you did not change your password recently, your account may be compromised!'); ?></small>
				<br>
				<small><?php echo $l->t('Please change your password to secure your account again.'); ?></small>
			</li>
			<?php endif; ?>
			<?php if (isset($_['invalidpassword']) && ($_['invalidpassword'])): ?>
			<a href="<?php echo OC_Helper::linkToRoute('core_lostpassword_index') ?>">
				<li class="errors">
					<?php echo $l->t('Lost your password?'); ?>
				</li>
			</a>
			<?php endif; ?>
		</ul>
		<p class="infield grouptop">
			<input type="text" name="user" id="user"
				   value="<?php echo $_['username']; ?>"<?php echo $_['user_autofocus'] ? ' autofocus' : ''; ?>
				   autocomplete="on" required/>
			<label for="user" class="infield"><?php echo $l->t('Username'); ?></label>
			<img class="svg" src="<?php echo image_path('', 'actions/user.svg'); ?>" alt=""/>
		</p>

		<p class="infield groupbottom">
			<input type="password" name="password" id="password" value="" data-typetoggle="#show"
				   required<?php echo $_['user_autofocus'] ? '' : ' autofocus'; ?> />
			<label for="password" class="infield"><?php echo $l->t('Password'); ?></label>
			<img class="svg" id="password-icon" src="<?php echo image_path('', 'actions/password.svg'); ?>" alt=""/>
			<input type="checkbox" id="show" name="show" />
			<label for="show"></label>
		</p>
		<input type="checkbox" name="remember_login" value="1" id="remember_login"/><label
			for="remember_login"><?php echo $l->t('remember'); ?></label>
		<input type="hidden" name="timezone-offset" id="timezone-offset"/>
		<input type="submit" id="submit" class="login primary" value="<?php echo $l->t('Log in'); ?>"/>
	</fieldset>
</form>
<?php if (!empty($_['alt_login'])) { ?>
<form id="alternative-logins">
	<fieldset>
		<legend><?php echo $l->t('Alternative Logins') ?></legend>
		<ul>
			<?php foreach($_['alt_login'] as $login): ?>
				<li><a class="button" href="<?php echo $login['href']; ?>" ><?php echo $login['name']; ?></a></li>
			<?php endforeach; ?>
		</ul>
	</fieldset>
</form>
<?php } ?>

<?php
OCP\Util::addscript('core', 'visitortimezone');

