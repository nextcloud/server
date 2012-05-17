<?php

global $profile;

?>

<div id="login">
	<img src="<?php echo OCP\image_path("", "logo.png"); ?>" alt="ownCloud" />
	<ul>
		<li class='error'>
			<div id="setup_form">
				<p><?php echo($l->t('This is an OpenID server endpoint. For more information, see '));?><a href='http://openid.net/' title='openid.net'>http://openid.net/</a></p>
				<?php if($_['user']):?>
					<p><?php echo($l->t('Identity: <b>').$profile['idp_url']); ?></b></p>
					<p><?php echo($l->t('Realm: <b>').$profile['php_realm']); ?></b></p>
					<p><?php echo($l->t('User: <b>').$_['user']); ?></b>
					<p><a href="<?php echo($profile['idp_url']); ?>?openid.mode=login"><?php echo($l->t('Login')); ?></a>
					<?php if($profile['allow_test'] === true): ?>
						<a href="<?php echo($profile['idp_url']); ?>?openid.mode=test">Test</a>
					<?php endif; ?>
				<?php else: ?>
					<p><?php echo($l->t('Error: <b>No user Selected')); ?></p>
				<?php endif; ?>
			</div>
		</li>
	</ul>
</div>

