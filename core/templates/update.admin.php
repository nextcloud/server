<div class="update">
	<form name="updateForm" class="updateForm">
		<h2 class="title bold">
		<?php p($l->t('%s will be updated to version %s.',
			array($_['productName'], $_['version']))); ?>
		</h2>
		<?php if (!empty($_['appList'])) { ?>
		<div class="section">
			<div class="title bold"><?php p($l->t('The following apps will be disabled during the upgrade:')) ?></div>
			<ul class="content appList">
			<?php foreach ($_['appList'] as $appInfo) { ?>
			<li><?php p($appInfo['name']) ?> (<?php p($appInfo['id']) ?>)</li>
			<?php } ?>
			</ul>
		</div>
		<?php } ?>
		<?php if (!empty($_['oldTheme'])) { ?>
		<div class="section">
			<div class="title bold"><?php p($l->t('The theme %s has been disabled.', array($_['oldTheme']))) ?></div>
		</div>
		<?php } ?>
		<div class="section">
			<div class="title bold"><?php p($l->t('Please make sure that the database and the data folder have been backed up before proceeding.')) ?></div>
		</div>
		<div class="section">
			<input type="submit" value="<?php p($l->t('Start upgrade')) ?>"></input>
		</div>
	</form>

	<div class="updateProgress hidden">
	</div>
</div>
