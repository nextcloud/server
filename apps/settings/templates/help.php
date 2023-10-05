<?php
\OC_Util::addStyle('settings', 'help');
?>

<div id="app-content">
	<div class="help-wrapper">
		<div class="help-content">
			<h2 class="help-content__heading">
				<?php p($l->t('Nextcloud help overview')); ?>
			</h2>
			<div class="help-content__body">
			<a class="button" target="_blank" rel="noreferrer noopener"
				href="<?php print_unescaped($_['urlAdminDocs']); ?>">
				<?php p($l->t('Administration documentation')); ?>  ↗
			</a>
			<a class="button" target="_blank" rel="noreferrer noopener"
				href="<?php print_unescaped($_['urlUserDocs']); ?>">
				<?php p($l->t('Account documentation')); ?>  ↗
			</a>
			<a href="https://docs.nextcloud.com" class="button" target="_blank" rel="noreferrer noopener">
				<?php p($l->t('General documentation')); ?> ↗
			</a>
			<a href="https://help.nextcloud.com" class="button" target="_blank" rel="noreferrer noopener">
				<?php p($l->t('Forum')); ?> ↗
			</a>
		</div>
	</div>
</div>
