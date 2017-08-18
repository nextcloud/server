<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */
/* @var $error boolean */
$error = $_['error'];
/* @var $error_message string */
$error_message = $_['error_message'];
/* @var $provider OCP\Authentication\TwoFactorAuth\IProvider */
$provider = $_['provider'];
/* @var $template string */
$template = $_['template'];
?>

<div class="warning">
	<h2 class="two-factor-header"><?php p($provider->getDisplayName()); ?></h2>
	<?php if ($error): ?>
			<?php if($error_message): ?>
				<p><strong><?php p($error_message); ?></strong></p>
			<?php else: ?>
				<p><strong><?php p($l->t('Error while validating your second factor')); ?></strong></p>
			<?php endif; ?>
	<?php endif; ?>
	<?php print_unescaped($template); ?>
	<p class="two-factor-link">
		<a class="button" href="<?php print_unescaped($_['logout_url']); ?>"><?php p($l->t('Cancel log in')) ?></a>
		<?php if (!is_null($_['backupProvider'])): ?>
		<a class="button" href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.TwoFactorChallenge.showChallenge',
												[
													'challengeProviderId' => $_['backupProvider']->getId(),
													'redirect_url' => $_['redirect_url'],
												]
											)) ?>"><?php p($l->t('Use backup code')) ?></a>
		<?php endif; ?>
	</p>
</div>
