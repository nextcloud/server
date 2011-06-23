<?php

global $profile;

?>

<div id="login">
	<img src="<?php echo image_path("", "weather-clear.png"); ?>" alt="ownCloud" />
	<ul>
		<li class='error'>
			<div id="setup_form">
			<p><?php echo($l->t('This is an OpenID server endpoint. For more information, see http://openid.net/'));?></p>
			<p><?php echo($l->t('Server: <b>').$profile['idp_url']); ?></b>
			<p><?php echo($l->t('Realm: <b>').$profile['php_realm']); ?></b>
			<p><a href="<?php echo($profile['idp_url']); ?>?openid.mode=login"><?php echo($l->t('Login')); ?></a>
			<?php if($profile['allow_test'] === true){ ?>
			| <a href="<?php echo($profile['idp_url']); ?>?openid.mode=test">Test</a>
			<?php } ?>
			</div>
		</li>
	</ul>
</div>

