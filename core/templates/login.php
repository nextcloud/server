<?php /** @var $l OC_L10N */ ?>
<?php
vendor_script('jsTimezoneDetect/jstz');
script('core', [
	'visitortimezone',
	'lostpassword',
	'login'
]);
?>

<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}</style><![endif]-->
<form method="post" name="login">
	<fieldset>
	<?php if (!empty($_['redirect_url'])) {
		print_unescaped('<input type="hidden" name="redirect_url" value="' . \OCP\Util::sanitizeHTML($_['redirect_url']) . '">');
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
		<?php if (isset($_['internalexception']) && ($_['internalexception'])): ?>
			<div class="warning">
				<?php p($l->t('An internal error occurred.')); ?><br>
				<small><?php p($l->t('Please try again or contact your administrator.')); ?></small>
			</div>
		<?php endif; ?>
		<div id="message" class="hidden">
			<img class="float-spinner" alt=""
				src="<?php p(image_path('core', 'loading-dark.gif'));?>">
			<span id="messageText"></span>
			<!-- the following div ensures that the spinner is always inside the #message div -->
			<div style="clear: both;"></div>
		</div>
		<p class="grouptop">
			<input type="text" name="user" id="user"
				placeholder="<?php p($l->t('Username or email')); ?>"
				value="<?php p($_['loginName']); ?>"
				<?php p($_['user_autofocus'] ? 'autofocus' : ''); ?>
				autocomplete="on" autocapitalize="off" autocorrect="off" required>
			<label for="user" class="infield"><?php p($l->t('Username or email')); ?></label>
		</p>

		<p class="groupbottom">
			<input type="password" name="password" id="password" value=""
				placeholder="<?php p($l->t('Password')); ?>"
				<?php p($_['user_autofocus'] ? '' : 'autofocus'); ?>
				autocomplete="on" autocapitalize="off" autocorrect="off" required>
			<label for="password" class="infield"><?php p($l->t('Password')); ?></label>
			<input type="submit" id="submit" class="login primary icon-confirm svg" title="<?php p($l->t('Log in')); ?>" value="" disabled="disabled"/>
		</p>

		<?php if (!empty($_['invalidpassword']) && !empty($_['canResetPassword'])) { ?>
		<a id="lost-password" class="warning" href="<?php p($_['resetPasswordLink']); ?>">
			<?php p($l->t('Wrong password. Reset it?')); ?>
		</a>
		<?php } else if (!empty($_['invalidpassword'])) { ?>
			<p class="warning">
				<?php p($l->t('Wrong password.')); ?>
			</p>
		<?php } ?>
		<?php if ($_['rememberLoginAllowed'] === true) : ?>
		<div class="remember-login-container">
			<?php if ($_['rememberLoginState'] === 0) { ?>
			<input type="checkbox" name="remember_login" value="1" id="remember_login" class="checkbox checkbox--white">
			<?php } else { ?>
			<input type="checkbox" name="remember_login" value="1" id="remember_login" class="checkbox checkbox--white" checked="checked">
			<?php } ?>
			<label for="remember_login"><?php p($l->t('Stay logged in')); ?></label>
		</div>
		<?php endif; ?>
		<input type="hidden" name="timezone-offset" id="timezone-offset"/>
		<input type="hidden" name="timezone" id="timezone"/>
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
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
