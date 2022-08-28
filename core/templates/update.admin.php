<div class="guest-box update" data-productname="<?php p($_['productName']) ?>" data-version="<?php p($_['version']) ?>">
	<div class="updateOverview">
		<?php if ($_['isAppsOnlyUpgrade']) { ?>
		<h2 class="title"><?php p($l->t('App update required')); ?></h2>
		<?php } else { ?>
		<h2 class="title"><?php p($l->t('%1$s will be updated to version %2$s',
			[$_['productName'], $_['version']])); ?></h2>
		<?php } ?>
		<?php if (!empty($_['appsToUpgrade'])) { ?>
		<div class="text-left">
			<span><?php p($l->t('The following apps will be updated:')); ?></span>
			<ul class="content appList">
				<?php foreach ($_['appsToUpgrade'] as $appInfo) { ?>
				<li><?php p($appInfo['name']) ?> (<?php p($appInfo['id']) ?>)</li>
				<?php } ?>
			</ul>
		</div>
		<?php } ?>
		<?php if (!empty($_['incompatibleAppsList'])) { ?>
		<div class="text-left">
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
			<?php p($l->t('The theme %s has been disabled.', [$_['oldTheme']])) ?>
		</div>
		<?php } ?>
		<div class="text-left margin-top bold">
			<?php p($l->t('Please make sure that the database, the config folder and the data folder have been backed up before proceeding.')) ?>
		</div>
		<input class="updateButton primary margin-top" type="button" value="<?php p($l->t('Start update')) ?>">
		<div class="notecard warning">
			<?php p($l->t('To avoid timeouts with larger installations, you can instead run the following command from your installation directory:')) ?>
			<pre>./occ upgrade</pre>
		</div>
	</div>

	<div class="update-progress hidden">
		<h2 id="update-progress-title"></h2>
		<div id="update-progress-icon" class="icon-loading-dark"></div>
		<p id="update-progress-message-error" class="hidden"></p>
		<ul id="update-progress-message-warnings" class="hidden"></ul>
		<p id="update-progress-message"></p>
		<a class="update-show-detailed"><?php p($l->t('Detailed logs')); ?> <span class="icon-caret-white"></span></a>
		<div id="update-progress-detailed" class="hidden"></div>
	</div>
</div>
