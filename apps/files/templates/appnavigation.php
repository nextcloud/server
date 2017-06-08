<div id="app-navigation">
	<div id="quota" class="section has-tooltip" title="<?php
	if ($_['quota'] === \OCP\Files\FileInfo::SPACE_UNLIMITED) {
		p($l->t('You are using %s of %s', [$_['usage'], $_['total_space']]));
	} else {
		p($l->t('You are using %s of %s (%s %%)', [$_['usage'], $_['total_space'],  $_['usage_relative']]));
	}
	?>">
		<div style="width:<?php p($_['usage_relative']);?>%"
			 <?php if($_['usage_relative'] > 80): ?>class="quota-warning"<?php endif; ?>>
			<p id="quotatext"><?php p($l->t('%s of %s in use', [$_['usage'], $_['total_space']])); ?></p>
		</div>
	</div>

	<ul class="with-icon">
		<?php foreach ($_['navigationItems'] as $item) { ?>
		<li data-id="<?php p($item['id']) ?>" class="nav-<?php p($item['id']) ?>">
			<a href="<?php p(isset($item['href']) ? $item['href'] : '#') ?>"
				class="nav-icon-<?php p($item['icon'] !== '' ? $item['icon'] : $item['id']) ?> svg">
				<?php p($item['name']);?>
			</a>
		</li>
		<?php } ?>
	</ul>
	<div id="app-settings">
		<div id="app-settings-header">
			<button class="settings-button" data-apps-slide-toggle="#app-settings-content">
				<?php p($l->t('Settings'));?>
			</button>
		</div>
		<div id="app-settings-content">
			<div id="files-setting-showhidden">
				<input class="checkbox" id="showhiddenfilesToggle" checked="checked" type="checkbox">
				<label for="showhiddenfilesToggle"><?php p($l->t('Show hidden files')); ?></label>
			</div>
			<label for="webdavurl"><?php p($l->t('WebDAV'));?></label>
			<input id="webdavurl" type="text" readonly="readonly" value="<?php p(\OCP\Util::linkToRemote('webdav')); ?>" />
			<em><?php print_unescaped($l->t('Use this address to <a href="%s" target="_blank" rel="noreferrer">access your Files via WebDAV</a>', array(link_to_docs('user-webdav'))));?></em>
		</div>
	</div>
</div>
