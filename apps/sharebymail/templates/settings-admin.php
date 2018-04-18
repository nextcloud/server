<?php
/** @var array $_ */

/** @var \OCP\IL10N $l */
script('sharebymail', 'settings-admin');
style('sharebymail', 'settings-admin');
?>
<div id="ncShareByMailSettings" class="section">
	<h2><?php p($l->t('Share by mail')); ?></h2>
	<p class="settings-hint"><?php p($l->t('Allows users to share a personalized link to a file or folder by putting in an email address.')); ?></p>

	<p>
		<input id="sendPasswordMail" type="checkbox" class="checkbox" <?php if($_['sendPasswordMail']) p('checked'); ?> />
		<label for="sendPasswordMail"><?php p($l->t('Send password by mail')); ?></label><br/>
		<input id="enforcePasswordProtection" type="checkbox" class="checkbox" <?php if($_['enforcePasswordProtection']) p('checked'); ?> />
		<label for="enforcePasswordProtection"><?php p($l->t('Enforce password protection')); ?></label>
	</p>

</div>
