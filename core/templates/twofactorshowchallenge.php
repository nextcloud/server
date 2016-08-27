<?php
/** @var $l OC_L10N */
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
		<p><?php p($l->t('An error occured while verifying the token')); ?></p>
		<?php endif; ?>
		<?php print_unescaped($template); ?>
</div>
<a class="two-factor-cancel" <?php print_unescaped($_['logout_attribute']); ?>><?php p($l->t('Cancel log in')) ?></a>
