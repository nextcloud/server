<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}</style><![endif]-->
<form method="post">
	<fieldset>
		<?php if(!empty($_['redirect_url'])) { echo '<input type="hidden" name="redirect_url" value="'.$_['redirect_url'].'" />'; } ?>
		<ul>
		<?php if(isset($_['invalidcookie']) && ($_['invalidcookie'])): ?>
			<li class="errors">
				<?php echo $l->t('Automatic logon rejected!'); ?><br>
				<small><?php echo $l->t('If you did not change your password recently, your account may be compromised!'); ?></small><br>
				<small><?php echo $l->t('Please change your password to secure your account again.'); ?></small>
			</li>
		<?php endif; ?>
		<?php if(isset($_['invalidpassword']) && ($_['invalidpassword'])): ?>
			<a href="<?php echo OC_Helper::linkToRoute('core_lostpassword_index') ?>"><li class="errors">
				<?php echo $l->t('Lost your password?'); ?>
			</li></a>
		<?php endif; ?>
		</ul>
		<p class="infield">
			<label for="user" class="infield"><?php echo $l->t( 'Username' ); ?></label>
			<input type="text" name="user" id="user" value="<?php echo $_['username']; ?>"<?php echo $_['user_autofocus']?' autofocus':''; ?> autocomplete="on" required />
		</p>
		<p class="infield">
			<label for="password" class="infield"><?php echo $l->t( 'Password' ); ?></label>
			<input type="password" name="password" id="password" value="" required<?php echo $_['user_autofocus']?'':' autofocus'; ?> />
		</p>
		<input type="checkbox" name="remember_login" value="1" id="remember_login" /><label for="remember_login"><?php echo $l->t('remember'); ?></label>
		<input type="submit" id="submit" class="login" value="<?php echo $l->t( 'Log in' ); ?>" />
	</fieldset>
</form>
