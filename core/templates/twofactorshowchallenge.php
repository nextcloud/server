<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */
/* @var $error boolean */
$error = $_['error'];
/* @var $provider OCP\Authentication\TwoFactorAuth\IProvider */
$provider = $_['provider'];
/* @var $template string */
$template = $_['template'];
?>

<div class="warning">
		<h2 class="two-factor-header"><?php p($provider->getDisplayName()); ?></h2>
		<?php if ($error): ?>
		<p><strong><?php p($l->t('Error while validating your second factor')); ?></strong></p>
		<?php endif; ?>
		<?php print_unescaped($template); ?>
</div>
<a class="two-factor-link" <?php print_unescaped($_['logout_attribute']); ?>><?php p($l->t('Cancel log in')) ?></a>
<?php if (!is_null($_['backupProvider'])): ?>
<a class="two-factor-link" href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.TwoFactorChallenge.showChallenge',
										[
											'challengeProviderId' => $_['backupProvider']->getId(),
											'redirect_url' => $_['redirect_url'],
										]
									)) ?>"><?php p($l->t('Use backup code')) ?></a>
<?php endif;
