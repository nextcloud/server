<div class="update">
	<form name="updateForm" class="updateForm">
		<p class="title bold">
		<?php p($l->t('%s will be updated to version %s.',
			array($_['productName'], $_['version']))); ?>
		</p>
		<?php if (!empty($_['appList'])) { ?>
		<div class="infogroup">
			<p class="bold"><?php p($l->t('The following apps will be disabled during the upgrade:')) ?></p>
			<ul class="content appList">
			<?php foreach ($_['appList'] as $appInfo) { ?>
			<li><?php p($appInfo['name']) ?> (<?php p($appInfo['id']) ?>)</li>
			<?php } ?>
			</ul>
		</div>
		<?php } ?>
		<?php if (!empty($_['oldTheme'])) { ?>
		<div class="infogroup">
			<p class="bold"><?php p($l->t('The theme %s has been disabled.', array($_['oldTheme']))) ?></p>
		</div>
		<?php } ?>
		<div class="infogroup">
			<p class="bold"><?php p($l->t('Please make sure that the database and the data folder have been backed up before proceeding.')) ?></p>
		</div>
		<div>
			<input type="submit" value="<?php p($l->t('Start upgrade')) ?>"></input>
		</div>
	</form>

	<div class="updateProgress hidden">
	</div>
</div>
