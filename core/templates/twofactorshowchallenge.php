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

<fieldset class="warning">
		<legend><strong><?php p($provider->getDisplayName()); ?></strong></legend>
		<p><?php p($l->t('Please authenticate using the selected factor.')) ?></p>
</fieldset>
<?php if ($error): ?>
<span class="warning"><?php p($l->t('An error occured while verifying the token')); ?></span>
<?php endif; ?>
<?php print_unescaped($template); ?>
<a class="two-factor-cancel" <?php print_unescaped($_['logout_attribute']); ?>><?php p($l->t('Cancel login')) ?></a>
