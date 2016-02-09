<?php script('updatenotification', 'admin') ?>
<form id="oca_updatenotification" class="section">
	<h2><?php p($l->t('Updater')); ?></h2>
	<p>
		<?php p($l->t('For security reasons the built-in ownCloud updater is using additional credentials. To visit the updater page please click the following button.')) ?>
	</p>
	<input type="button" id="oca_updatenotification" value="<?php p($l->t('Open updater')) ?>">
</form>
