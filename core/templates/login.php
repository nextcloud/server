<?php /** @var $l OC_L10N */ ?>
<?php
vendor_script('jsTimezoneDetect/jstz');
script('core', [
	'visitortimezone',
	'lostpassword'
]);
?>

<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}</style><![endif]-->
<form method="post" name="login">
	<fieldset>
	<?php if (!empty($_['redirect_url'])) {
		print_unescaped('<input type="hidden" name="redirect_url" value="' . OC_Util::sanitizeHTML($_['redirect_url']) . '" />');
	} ?>
		<?php if (isset($_['apacheauthfailed']) && ($_['apacheauthfailed'])): ?>
			<div class="warning">
				<?php p($l->t('Server side authentication failed!')); ?><br>
				<small><?php p($l->t('Please contact your administrator.')); ?></small>
			</div>
		<?php endif; ?>
		<?php foreach($_['messages'] as $message): ?>
			<div class="warning">
				<?php p($message); ?><br>
			</div>
		<?php endforeach; ?>
		<p id="message" class="hidden">
			<img class="float-spinner" alt=""
				src="<?php p(\OCP\Util::imagePath('core', 'loading-dark.gif'));?>" />
			<span id="messageText"></span>
			<!-- the following div ensures that the spinner is always inside the #message div -->
			<div style="clear: both;"></div>
		</p>
		<p class="grouptop">
			<input type="text" name="user" id="user"
				placeholder="<?php p($l->t('Username')); ?>"
				value="<?php p($_['username']); ?>"
				<?php p($_['user_autofocus'] ? 'autofocus' : ''); ?>
				autocomplete="on" autocapitalize="off" autocorrect="off" required />
			<label for="user" class="infield"><?php p($l->t('Username')); ?></label>
			<img class="svg" src="<?php print_unescaped(image_path('', 'actions/user.svg')); ?>" alt=""/>
		</p>

		<p class="groupbottom">
			<input type="password" name="password" id="password" value=""
				placeholder="<?php p($l->t('Password')); ?>"
				<?php p($_['user_autofocus'] ? '' : 'autofocus'); ?>
				autocomplete="on" autocapitalize="off" autocorrect="off" required />
			<label for="password" class="infield"><?php p($l->t('Password')); ?></label>
			<img class="svg" id="password-icon" src="<?php print_unescaped(image_path('', 'actions/password.svg')); ?>" alt=""/>
		</p>

		<?php if (isset($_['invalidpassword']) && ($_['invalidpassword'])): ?>
		<a id="lost-password" class="warning" href="">
			<?php p($l->t('Forgot your password? Reset it!')); ?>
		</a>
		<?php endif; ?>
		<?php if ($_['rememberLoginAllowed'] === true) : ?>
		<input type="checkbox" name="remember_login" value="1" id="remember_login" />
		<label for="remember_login"><?php p($l->t('remember')); ?></label>
		<?php endif; ?>
		<input type="hidden" name="timezone-offset" id="timezone-offset"/>
		<input type="hidden" name="timezone" id="timezone"/>
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
		<input type="submit" id="submit" class="login primary" value="<?php p($l->t('Log in')); ?>" disabled="disabled"/>
	</fieldset>
</form>
<?php if (!empty($_['alt_login'])) { ?>
<form id="alternative-logins">
	<fieldset>
		<legend><?php p($l->t('Alternative Logins')) ?></legend>
		<ul>
			<?php foreach($_['alt_login'] as $login): ?>
				<li><a class="button" href="<?php print_unescaped($login['href']); ?>" ><?php p($login['name']); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</fieldset>
</form>
<?php }

