<?php /** @var $l \OCP\IL10N */ ?>
<?php
vendor_script('jsTimezoneDetect/jstz');
script('core', 'merged-login');

use OC\Core\Controller\LoginController;
?>

<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}</style><![endif]-->
<form method="post" name="login">
	<fieldset>
	<?php if (!empty($_['redirect_url'])) {
		print_unescaped('<input type="hidden" name="redirect_url" value="' . \OCP\Util::sanitizeHTML($_['redirect_url']) . '">');
	} ?>
		<?php if (isset($_['apacheauthfailed']) && $_['apacheauthfailed']): ?>
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
		<?php if (isset($_['internalexception']) && $_['internalexception']): ?>
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
		<p class="grouptop<?php if (!empty($_[LoginController::LOGIN_MSG_INVALIDPASSWORD])) { ?> shake<?php } ?>">
			<input type="text" name="user" id="user"
				placeholder="<?php p($l->t('Username or email')); ?>"
				aria-label="<?php p($l->t('Username or email')); ?>"
				value="<?php p($_['loginName']); ?>"
				<?php p($_['user_autofocus'] ? 'autofocus' : ''); ?>
				autocomplete="on" autocapitalize="none" autocorrect="off" required>
			<label for="user" class="infield"><?php p($l->t('Username or email')); ?></label>
		</p>

		<p class="groupbottom<?php if (!empty($_[LoginController::LOGIN_MSG_INVALIDPASSWORD])) { ?> shake<?php } ?>">
			<input type="password" name="password" id="password" value=""
				placeholder="<?php p($l->t('Password')); ?>"
				aria-label="<?php p($l->t('Password')); ?>"
				<?php p($_['user_autofocus'] ? '' : 'autofocus'); ?>
				autocomplete="on" autocapitalize="off" autocorrect="none" required>
			<label for="password" class="infield"><?php p($l->t('Password')); ?></label>
		</p>

		<div id="submit-wrapper">
			<input type="submit" id="submit" class="login primary" title="" value="<?php p($l->t('Log in')); ?>" disabled="disabled" />
			<div class="submit-icon icon-confirm-white"></div>
		</div>

		<?php if (!empty($_[LoginController::LOGIN_MSG_INVALIDPASSWORD])) { ?>
			<p class="warning wrongPasswordMsg">
				<?php p($l->t('Wrong password.')); ?>
			</p>
		<?php } else if (!empty($_[LoginController::LOGIN_MSG_USERDISABLED])) { ?>
			<p class="warning userDisabledMsg">
				<?php p(\OC::$server->getL10N('lib')->t('User disabled')); ?>
			</p>
		<?php } ?>

		<?php if ($_['throttle_delay'] > 5000) { ?>
			<p class="warning throttledMsg">
				<?php p($l->t('We have detected multiple invalid login attempts from your IP. Therefore your next login is throttled up to 30 seconds.')); ?>
			</p>
		<?php } ?>

		<?php if (!empty($_['canResetPassword'])) { ?>
		<div id="reset-password-wrapper" style="display: none;">
			<input type="submit" id="reset-password-submit" class="login primary" title="" value="<?php p($l->t('Reset password')); ?>" disabled="disabled" />
			<div class="submit-icon icon-confirm-white"></div>
		</div>
		<?php } ?>

		<div class="login-additional">
			<?php if (!empty($_['canResetPassword'])) { ?>
			<div class="lost-password-container">
				<a id="lost-password" href="<?php p($_['resetPasswordLink']); ?>">
					<?php p($l->t('Forgot password?')); ?>
				</a>
				<a id="lost-password-back" href="" style="display:none;">
					<?php p($l->t('Back to login')); ?>
				</a>
			</div>
			<?php } ?>
		</div>

		<input type="hidden" name="timezone_offset" id="timezone_offset"/>
		<input type="hidden" name="timezone" id="timezone"/>
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
	</fieldset>
</form>
<?php if (!empty($_['alt_login'])) { ?>
<form id="alternative-logins">
	<fieldset>
		<ul>
			<?php foreach($_['alt_login'] as $login): ?>
				<li><a class="button" href="<?php print_unescaped($login['href']); ?>" ><?php p($login['name']); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</fieldset>
</form>
<?php }
