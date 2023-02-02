<?php
	/** @var array $_ */
	/** @var \OCP\IL10N $l */
	\OCP\Util::addStyle('core', 'guest');
	\OCP\Util::addStyle('core', 'publicshareauth');
	\OCP\Util::addScript('core', 'publicshareauth');
	?>

<div class="guest-box">
	<!-- password prompt form. It should be hidden when we show the email prompt form -->
	<?php if (!isset($_['identityOk'])): ?>
		<form method="post" id="password-input-form">
	<?php else: ?>
		<form method="post" id="password-input-form" style="display:none;">
	<?php endif; ?>
		<fieldset class="warning">
			<?php if (!isset($_['wrongpw'])): ?>
				<div class="warning-info"><?php p($l->t('This share is password-protected')); ?></div>
			<?php endif; ?>
			<?php if (isset($_['wrongpw'])): ?>
				<div class="warning wrongPasswordMsg"><?php p($l->t('The password is wrong or expired. Please try again or request a new one.')); ?></div>
			<?php endif; ?>
			<p>
				<label for="password" class="infield"><?php p($l->t('Password')); ?></label>
				<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
				<input type="password" name="password" id="password"
					placeholder="<?php p($l->t('Password')); ?>" value=""
					autocomplete="new-password" autocapitalize="off" autocorrect="off"
					autofocus />
				<input type="hidden" name="sharingToken" value="<?php p($_['share']->getToken()) ?>" id="sharingToken">
				<input type="hidden" name="sharingType" value="<?php p($_['share']->getShareType()) ?>" id="sharingType">
				<input type="submit" id="password-submit"
					class="svg icon-confirm input-button-inline" value="" disabled="disabled" />
			</p>
		</fieldset>
	</form>
	
	<!-- email prompt form. It should initially be hidden -->
	<?php if (isset($_['identityOk'])): ?>
		<form method="post" id="email-input-form">
	<?php else: ?>
		<form method="post" id="email-input-form" style="display:none;">
	<?php endif; ?>
		<fieldset class="warning">
			<div class="warning-info" id="email-prompt"><?php p($l->t('Please type in your email address to request a temporary password')); ?></div>
			 <p>
				<input type="email" id="email" name="identityToken" placeholder="<?php p($l->t('Email address')); ?>" />
				<input type="submit" id="password-request" name="passwordRequest" class="svg icon-confirm input-button-inline" value="" disabled="disabled"/>
				<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
				<input type="hidden" name="sharingToken" value="<?php p($_['share']->getToken()) ?>" id="sharingToken">
				<input type="hidden" name="sharingType" value="<?php p($_['share']->getShareType()) ?>" id="sharingType">
			</p>
			<?php if (isset($_['identityOk'])): ?>
				<?php if ($_['identityOk']): ?>
					<div class="warning-info" id="identification-success"><?php p($l->t('Password sent!')); ?></div>
				<?php else: ?>
					<div class="warning" id="identification-failure"><?php p($l->t('You are not authorized to request a password for this share')); ?></div>
				<?php endif; ?>
			<?php endif; ?>
		</fieldset>
	</form>
	
	<!-- request password button -->
	<?php if (!isset($_['identityOk']) && $_['share']->getShareType() === $_['share']::TYPE_EMAIL && !$_['share']->getSendPasswordByTalk()): ?>
		<a id="request-password-button-not-talk"><?php p($l->t('Forgot password?')); ?></a>
	<?php endif; ?>
	
	<!-- back to showShare button -->
	<form method="get">
		<fieldset>
			<a
				href=""
				id="request-password-back-button"
	<?php if (isset($_['identityOk'])): ?>
				style="display:block;">
	<?php else: ?>
				style="display:none;">
	<?php endif; ?>
				<?php p($l->t('Back')); ?></a>
		</fieldset>
	</form>
</div>
