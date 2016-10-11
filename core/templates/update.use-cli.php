<div class="update" data-productname="<?php p($_['productName']) ?>" data-version="<?php p($_['version']) ?>">
	<div class="updateOverview">
		<h2 class="title"><?php p($l->t('Update needed')) ?></h2>
		<div class="infogroup">
			<?php if ($_['tooBig']) {
				p($l->t('Please use the command line updater because you have a big instance.'));
			} else {
				p($l->t('Please use the command line updater because automatic updating is disabled in the config.php.'));
			} ?><br><br>
			<?php
			print_unescaped($l->t('For help, see the  <a target="_blank" rel="noreferrer" href="%s">documentation</a>.', [link_to_docs('admin-cli-upgrade')])); ?><br><br>
		</div>
	</div>
</div>
