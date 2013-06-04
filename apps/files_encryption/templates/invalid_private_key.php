<ul>
	<li class='error'>
		<?php $location = \OC_Helper::linkToRoute( "settings_personal" ).'#changePKPasswd' ?>

		<?php p($l->t('Your private key is not valid! Maybe the your password was changed from outside.')); ?>
		<br/>
		<?php p($l->t('You can unlock your private key in your ')); ?> <a href="<?php echo $location?>"><?php p($l->t('personal settings')); ?>.</a>
		<br/>
	</li>
</ul>
