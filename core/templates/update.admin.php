<div class="update" data-productname="<?php p($_['productName']) ?>" data-version="<?php p($_['version']) ?>">
	<div class="updateOverview">
		<?php if ($_['isAppsOnlyUpgrade']) { ?>
		<h2 class="title"><?php p($l->t('App update required')); ?></h2>
		<?php } else { ?>
		<h2 class="title"><?php p($l->t('%s will be updated to version %s',
			array($_['productName'], $_['version']))); ?></h2>
		<?php } ?>
		<?php if (!empty($_['appsToUpgrade'])) { ?>
		<div class="infogroup">
			<span><?php p($l->t('These apps will be updated:')); ?></span>
			<ul class="content appList">
				<?php foreach ($_['appsToUpgrade'] as $appInfo) { ?>
				<li><?php p($appInfo['name']) ?> (<?php p($appInfo['id']) ?>)</li>
				<?php } ?>
			</ul>
		</div>
		<?php } ?>
		<?php if (!empty($_['incompatibleAppsList'])) { ?>
		<div class="infogroup">
			<span><?php p($l->t('These incompatible apps will be disabled:')) ?></span>
			<ul class="content appList">
				<?php foreach ($_['incompatibleAppsList'] as $appInfo) { ?>
				<li><?php p($appInfo['name']) ?> (<?php p($appInfo['id']) ?>)</li>
				<?php } ?>
			</ul>
		</div>
		<?php } ?>
		<?php if (!empty($_['oldTheme'])) { ?>
		<div class="infogroup">
			<?php p($l->t('The theme %s has been disabled.', array($_['oldTheme']))) ?>
		</div>
		<?php } ?>
		<div class="infogroup bold">
			<?php p($l->t('Please make sure that the database, the config folder and the data folder have been backed up before proceeding.')) ?>
		</div>
		<?php foreach ($_['releaseNotes'] as $note): ?>
		<div class="infogroup bold">
			<?php p($note) ?>
		</div>
		<?php endforeach; ?>
		<input class="updateButton" type="button" value="<?php p($l->t('Start update')) ?>">
		<div class="infogroup">
			<?php p($l->t('To avoid timeouts with larger installations, you can instead run the following command from your installation directory:')) ?>
			<pre>./occ upgrade</pre>
		</div>
	</div>

	<div class="updateProgress hidden"></div>
</div>
