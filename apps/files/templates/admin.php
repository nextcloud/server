	<?php OCP\Util::addscript('files', 'admin'); ?>

	<div class="section">
		<h2><?php p($l->t('File handling')); ?></h2>
		<label for="maxUploadSize"><?php p($l->t( 'Maximum upload size' )); ?> </label>
		<span id="maxUploadSizeSettingsMsg" class="msg"></span>
		<br />
		<input type="text" name='maxUploadSize' id="maxUploadSize" value='<?php p($_['uploadMaxFilesize']) ?>' <?php if(!$_['uploadChangable']) { p('disabled'); } ?> />
		<?php if($_['displayMaxPossibleUploadSize']):?>
			(<?php p($l->t('max. possible: ')); p($_['maxPossibleUploadSize']) ?>)
		<?php endif;?>
		<input type="hidden" value="<?php p($_['requesttoken']); ?>" name="requesttoken" />
		<?php if($_['uploadChangable']): ?>
			<input type="submit" id="submitMaxUpload"
				   value="<?php p($l->t( 'Save' )); ?>"/>
			<p><em><?php p($l->t('With PHP-FPM it might take 5 minutes for changes to be applied.')); ?></em></p>
		<?php else: ?>
			<p><em><?php p($l->t('Missing permissions to edit from here.')); ?></em></p>
		<?php endif; ?>
	</div>
