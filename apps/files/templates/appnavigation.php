<div id="app-navigation">
	<ul class="with-icon">
		<?php
		$pinned = 0;
		$trashelement = null;
		?>

		<?php foreach ($_['navigationItems'] as $item) {
			strpos($item['classes'], 'pinned')!==false ? $pinned++ : '';
			//var_dump($_['navigationItems']);
			if($item['id']=="trashbin"){
				$trashelement=$item;
				break;
			}
			?>


			<li data-id="<?php p($item['id']) ?>" class="nav-<?php p($item['id']) ?> <?php p($item['classes']) ?> <?php p($pinned===1?'first-pinned':'') ?>">
				<a href="<?php p(isset($item['href']) ? $item['href'] : '#') ?>"
				   class="nav-icon-<?php p($item['icon'] !== '' ? $item['icon'] : $item['id']) ?> svg">
					<?php p($item['name']);?>
				</a>
			</li>
		<?php } ?>


		<?php if($_['favoritesFolders']>0){

			?>

			<li class="nav-sidebar-spacer">
				<?php p($l->t('Favorites'));?>:
			</li>
		<?php } ?>

		<?php $pinned = 0; ?>

		<?php foreach ($_['favoritesFolders'] as $item) { ?>

			<li data-id=<?php echo $item['path']; ?>>
				<a class="nav-icon-files svg" href=<?php echo $item['serverroot']."/index.php/apps/files/?dir=".$item['path']; ?>><?php echo $item['name']; ?></a>
			</li>

		<?php }?>

		<?php

		if(isset($trashelement)){
			strpos($trashelement['classes'], 'pinned')!==false ? $pinned++ : '';
			?>
			<li data-id="<?php p($trashelement['id']) ?>" class="nav-<?php p($trashelement['id']) ?> <?php p($trashelement['classes']) ?> <?php p($pinned===1?'first-pinned':'') ?>">
				<a href="<?php p(isset($trashelement['href']) ? $trashelement['href'] : '#') ?>"
				   class="nav-icon-<?php p($trashelement['icon'] !== '' ? $trashelement['icon'] : $trashelement['id']) ?> svg">
					<?php p($trashelement['name']);?>
				</a>
			</li>
		<?php } ?>


		<li id="quota" class="pinned <?php p($pinned===0?'first-pinned ':'') ?><?php
		if ($_['quota'] !== \OCP\Files\FileInfo::SPACE_UNLIMITED) {
		?>has-tooltip" title="<?php p($_['usage_relative'] . '%');
		} ?>">
			<a href="#" class="nav-icon-quota svg">
				<p id="quotatext"><?php
					if ($_['quota'] !== \OCP\Files\FileInfo::SPACE_UNLIMITED) {
						p($l->t('%s of %s used', [$_['usage'], $_['total_space']]));
					} else {
						p($l->t('%s used', [$_['usage']]));
					} ?></p>
				<div class="quota-container">
					<progress value="<?php p($_['usage_relative']); ?>" max="100"
						<?php if($_['usage_relative'] > 80): ?> class="warn" <?php endif; ?>></progress>
				</div>
			</a>
		</li>
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
			<div id="files-setting-showFavoriteQuickAccess">
				<input class="checkbox" id="showfavoritequickaccessToggle" checked="checked" type="checkbox">
				<label for="showfavoritequickaccessToggle"><?php p($l->t('Enable Favorites Quick Access')); ?></label>
			</div>
			<label for="webdavurl"><?php p($l->t('WebDAV'));?></label>
			<input id="webdavurl" type="text" readonly="readonly" value="<?php p(\OCP\Util::linkToRemote('webdav')); ?>" />
			<em><?php print_unescaped($l->t('Use this address to <a href="%s" target="_blank" rel="noreferrer noopener">access your Files via WebDAV</a>', array(link_to_docs('user-webdav'))));?></em>
		</div>
	</div>

</div>
