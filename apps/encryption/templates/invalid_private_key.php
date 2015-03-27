<ul>
	<li class='error'>
		<?php $location = \OC_Helper::linkToRoute( "settings_personal" ).'#changePKPasswd' ?>

		<?php p($_['message']); ?>
		<br/>
		<?php if($_['errorCode'] === \OCA\Files_Encryption\Crypt::ENCRYPTION_PRIVATE_KEY_NOT_VALID_ERROR): ?>
			<?php p($l->t('Go directly to your %spersonal settings%s.', array('<a href="'.$location.'">', '</a>'))); ?>
		<?php endif; ?>
		<br/>
	</li>
</ul>
