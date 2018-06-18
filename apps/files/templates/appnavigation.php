<div id="app-navigation">
	<ul class="with-icon">

		<?php $pinned = 0 ?>
		<?php foreach ($_['navigationItems'] as $item) {
			strpos($item['classes'], 'pinned')!==false ? $pinned++ : '';
			?>

			<li data-id="<?php p(isset($item['href']) ? $item['href'] : $item['id']) ?>"
				class="nav-<?php p($item['id']) ?> <?php p($item['classes']) ?> <?php p($pinned===1?'first-pinned':'') ?> <?php if($item['enableQuickaccess']=='true'){ ?> open<?php } ?>"
				<?php if(isset($item['folderPosition'])){ ?> folderPos="<?php p($item['folderPosition']);?>"<?php } ?>
				<?php if($item['id']=='favorites'){?>id="favorites-toggle"<?php } ?>>

				<?php if($item['id']=='favorites'){?>
				<button id="button-collapseQuickAccess" class="collapse" <?php if($item['favoritescount']==0){ ?> style="display: none"<?php } ?>></button><?php } ?>

				<a href="<?php p(isset($item['href']) ? $item['href'] : '#') ?>"
				   class="nav-icon-<?php p($item['icon'] !== '' ? $item['icon'] : $item['id']) ?> svg"><?php p($item['name']);?></a>
				<?php if($item['id']=='favorites'){?>
					<div id="quickaccessbutton" class="app-navigation-entry-utils" <?php if($item['favoritescount']==0){ ?> style="display: none"<?php } ?>>
						<ul>
							<li class="app-navigation-entry-utils-menu-button svg">
								<button id="button-<?php p($item['id']) ?>"></button>
							</li>
						</ul>
					</div>
				<div class="app-navigation-entry-menu" id="menu-<?php p($item['id']) ?>">
					<ul>
						<li>
							<span class="menuitem">
								<input id="enableQuickAccess" type="checkbox" class="checkbox"  <?php if($item['enableQuickaccess']=='true'){ ?> checked<?php } ?>/>
								<label for="enableQuickAccess"><?php p($l->t('Enable Quickaccess')); ?></label>
							</span>
						</li>
						<li>
							<span class="menuitem">
								<input id="sortByAlphabet" type="checkbox" class="checkbox" data-group='SortingStrategy'<?php if($item['quickaccessSortingStrategy']=='alphabet'){ ?> checked<?php } ?>/>
								<label for="sortByAlphabet"><?php p($l->t('Sort by Alphabet')); ?></label>
							</span>
						</li>
						<li>
							<span class="menuitem">
								<input id="sortByDate" type="checkbox" class="checkbox" data-group='SortingStrategy'<?php if($item['quickaccessSortingStrategy']=='date'){ ?> checked<?php } ?>/>
								<label for="sortByDate"><?php p($l->t('Sort by Date')); ?></label>
							</span>
						</li>
						<li>
							<span class="menuitem">
								<input id="enableReverse" type="checkbox" class="checkbox" <?php if($item['quickaccessSortingReverse']==true){ ?> checked<?php } ?>/>
								<label for="enableReverse"><?php p($l->t('Reverse List')); ?></label>
							</span>
						</li>
					</ul>
				</div>
					<ul id="quickaccess-list" >
					<?php /*This fixes the styleerrors if no favorites are set*/ if($item['favoritescount']==0){?></ul><?php } ?>
				<?php } ?>

				<?php if($item['quickaccesselement']=='last'){?>
				</ul>
				<?php } ?>
			</li>

		<?php } ?>

		<li id="quota" class="pinned <?php p($pinned===0?'first-pinned ':'') ?><?php
		if ($_['quota'] !== \OCP\Files\FileInfo::SPACE_UNLIMITED) {
		?>has-tooltip" title="<?php p($_['usage_relative'] . '%');
		} ?>">
			<a href="#" class="icon-quota svg">
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
			<label for="webdavurl"><?php p($l->t('WebDAV'));?></label>
			<input id="webdavurl" type="text" readonly="readonly" value="<?php p(\OCP\Util::linkToRemote('webdav')); ?>" />
			<em><?php print_unescaped($l->t('Use this address to <a href="%s" target="_blank" rel="noreferrer noopener">access your Files via WebDAV</a>', array(link_to_docs('user-webdav'))));?></em>
		</div>
	</div>

</div>
