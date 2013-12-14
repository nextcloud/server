<ul>
	<li class='error'>
		<?php $location = \OC_Helper::linkToRoute( "settings_personal" ).'#changePKPasswd' ?>

		<?php p($_['message']); ?>
		<br/>
		<?php if($_['errorCode'] === \OCA\Encryption\Crypt::ENCRYPTION_PRIVATE_KEY_NOT_VALID_ERROR): ?>
			<?php>p($l->t('Go directly to your ')); ?> <a href="<?php echo $location?>"><?php p($l->t('personal settings')); ?>.</a>
		<?php endif; ?>
		<br/>
	</li>
</ul>
