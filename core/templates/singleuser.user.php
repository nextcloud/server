<ul>
	<li class='update'>
		<?php p($l->t('This Nextcloud instance is currently in single user mode.')) ?><br><br>
		<?php p($l->t('This means only administrators can use the instance.')) ?><br><br>
		<?php p($l->t('Contact your system administrator if this message persists or appeared unexpectedly.')) ?>
		<br><br>
		<?php p($l->t('Thank you for your patience.')); ?><br><br>
		<a class="button" <?php print_unescaped(OC_User::getLogoutAttribute()); ?>><?php p($l->t('Log out')); ?></a>
	</li>
</ul>
