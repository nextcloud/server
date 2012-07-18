<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}</style><![endif]-->
<form action="index.php" method="post">
	<fieldset>
		<?php if(!empty($_['redirect'])) { echo '<input type="hidden" name="redirect_url" value="'.htmlentities($_['redirect']).'" />'; } ?>
		<?php if($_['error']): ?>
			<a href="./core/lostpassword/"><?php echo $l->t('Lost your password?'); ?></a>
		<?php endif; ?>
		<p class="infield">
			<label for="user" class="infield"><?php echo $l->t( 'Username' ); ?></label>
			<input type="text" name="user" id="user" value="<?php echo !empty($_POST['user'])?htmlentities($_POST['user'],ENT_COMPAT,'utf-8').'"':'" autofocus'; ?> autocomplete="on" required />
		</p>
		<p class="infield">
			<label for="password" class="infield"><?php echo $l->t( 'Password' ); ?></label>
			<input type="password" name="password" id="password" value="" required <?php echo !empty($_POST['user'])?'autofocus':''; ?> />
			<input type="hidden" name="sectoken" id="sectoken" value="<?php echo($_['sectoken']); ?>"  />
		</p>
		<input type="checkbox" name="remember_login" value="1" id="remember_login" /><label for="remember_login"><?php echo $l->t('remember'); ?></label>
		<input type="submit" id="submit" class="login" value="<?php echo $l->t( 'Log in' ); ?>" />
	</fieldset>
</form>
