<div class="update" data-productname="<?php p($_['productName']) ?>" data-version="<?php p($_['version']) ?>">
	<div class="updateOverview">
		<?php if ($_['isAppsOnlyUpgrade']) { ?>
		<h2 class="title bold"><?php p($l->t('The following apps will be updated:')); ?></h2>
		<?php } else { ?>
		<h2 class="title bold"><?php p($l->t('%s will be updated to version %s.',
			array($_['productName'], $_['version']))); ?></h2>
		<?php } ?>
		<?php if (!empty($_['appsToUpgrade'])) { ?>
		<div class="infogroup">
			<ul class="content appList">
				<?php foreach ($_['appsToUpgrade'] as $appInfo) { ?>
				<li><?php p($appInfo['name']) ?> (<?php p($appInfo['id']) ?>)</li>
				<?php } ?>
			</ul>
		</div>
		<?php } ?>
		<?php if (!empty($_['appList'])) { ?>
		<div class="infogroup">
			<span class="bold"><?php p($l->t('The following apps will be disabled:')) ?></span>
			<ul class="content appList">
				<?php foreach ($_['appList'] as $appInfo) { ?>
				<li><?php p($appInfo['name']) ?> (<?php p($appInfo['id']) ?>)</li>
				<?php } ?>
			</ul>
		</div>
		<?php } ?>
		<?php if (!empty($_['oldTheme'])) { ?>
		<div class="infogroup bold">
			<?php p($l->t('The theme %s has been disabled.', array($_['oldTheme']))) ?>
		</div>
		<?php } ?>
		<?php if (!$_['isAppsOnlyUpgrade']) { ?>
		<div class="infogroup bold">
			<?php p($l->t('Please make sure that the database, the config folder and the data folder have been backed up before proceeding.')) ?>
		</div>
		<?php } ?>
		<input class="updateButton" type="button" value="<?php p($l->t('Start update')) ?>">
		<div class="infogroup">
			<?php p($l->t('To avoid timeouts with larger installations, you can instead run the following command from your installation directory:')) ?>
			<pre>./occ upgrade</pre>
		</div>
	</div>

	<div class="updateProgress hidden"></div>
</div>
