<div class="guest-box update" data-productname="<?php p($_['productName']) ?>" data-version="<?php p($_['version']) ?>">
	<div class="updateOverview">
		<h2 class="title"><?php p($l->t('Update needed')) ?></h2>
		<div class="text-left">
			<?php if ($_['tooBig']) {
	p($l->t('Please use the command line updater because you have a big instance with more than 50 users.'));
} else {
	p($l->t('Please use the command line updater because automatic updating is disabled in the config.php.'));
} ?><br><br>
			<?php
			print_unescaped($l->t('For help, see the  <a target="_blank" rel="noreferrer noopener" href="%s">documentation</a>.', [link_to_docs('admin-cli-upgrade')])); ?>
		</div>
	</div>

	<?php if ($_['tooBig']) { ?>
		<div class="notecard warning">
			<p><?php p($l->t('I know that if I continue doing the update via web UI has the risk, that the request runs into a timeout and could cause data loss, but I have a backup and know how to restore my instance in case of a failure.')); ?></p>
			<a class="button error margin-top" href="?IKnowThatThisIsABigInstanceAndTheUpdateRequestCouldRunIntoATimeoutAndHowToRestoreABackup=IAmSuperSureToDoThis"><?php p($l->t('Upgrade via web on my own risk')); ?></a>
		</div>
	<?php } ?>
</div>
