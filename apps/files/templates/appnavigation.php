<div id="app-navigation-files" role="navigation"></div>
<div class="hidden">
	<ul class="with-icon" tabindex="0">
		<?php
			$pinned = 0;
		foreach ($_['navigationItems'] as $item) {
			$pinned = NavigationListElements($item, $l, $pinned);
		}
		?>
	</ul>
</div>


<?php

/**
 * Prints the HTML for a single Entry.
 *
 * @param $item The item to be added
 * @param $l Translator
 * @param $pinned IntegerValue to count the pinned entries at the bottom
 *
 * @return int Returns the pinned value
 */
function NavigationListElements($item, $l, $pinned) {
	strpos($item['classes'] ?? '', 'pinned') !== false ? $pinned++ : ''; ?>
	<li
		data-id="<?php p($item['id']) ?>"
		<?php if (isset($item['dir'])) { ?> data-dir="<?php p($item['dir']); ?>" <?php } ?>
		<?php if (isset($item['view'])) { ?> data-view="<?php p($item['view']); ?>" <?php } ?>
		<?php if (isset($item['expandedState'])) { ?> data-expandedstate="<?php p($item['expandedState']); ?>" <?php } ?>
		class="nav-<?php p($item['id']) ?>
		<?php if (isset($item['classes'])) {
			p($item['classes']);
		} ?>
		<?php p($pinned === 1 ? 'first-pinned' : '') ?>
		<?php if (isset($item['defaultExpandedState']) && $item['defaultExpandedState']) { ?> open<?php } ?>"
		<?php if (isset($item['folderPosition'])) { ?> folderposition="<?php p($item['folderPosition']); ?>" <?php } ?>>

		<a href="<?php p($item['href'] ?? '#') ?>"
		   class="nav-icon-<?php p(isset($item['icon']) && $item['icon'] !== '' ? $item['icon'] : $item['id']) ?> svg"><?php p($item['name']); ?></a>


		<?php
			NavigationElementMenu($item);
	if (isset($item['sublist'])) {
		?>
			<button class="collapse app-navigation-noclose" aria-expanded="<?= !empty($item['defaultExpandedState']) ? 'true' : 'false' ?>"
				aria-label="<?php p($l->t('Toggle %1$s sublist', $item['name'])) ?>"
				<?php if (sizeof($item['sublist']) == 0) { ?> style="display: none" <?php } ?>>
			</button>
			<ul id="sublist-<?php p($item['id']); ?>">
				<?php
				foreach ($item['sublist'] as $item) {
					$pinned = NavigationListElements($item, $l, $pinned);
				} ?>
			</ul>
		<?php
	} ?>
	</li>


	<?php
	return $pinned;
}

/**
 * Prints the HTML for a dotmenu.
 *
 * @param $item The item to be added
 *
 * @return void
 */
function NavigationElementMenu($item) {
	if (isset($item['menubuttons']) && $item['menubuttons'] === 'true') {
		?>
		<div id="dotmenu-<?php p($item['id']); ?>"
			 class="app-navigation-entry-utils" <?php if (isset($item['enableMenuButton']) && $item['enableMenuButton'] === 0) { ?> style="display: none"<?php } ?>>
			<ul>
				<li class="app-navigation-entry-utils-menu-button svg">
					<button id="dotmenu-button-<?php p($item['id']) ?>"></button>
				</li>
			</ul>
		</div>
		<div id="dotmenu-content-<?php p($item['id']) ?>"
			 class="app-navigation-entry-menu">
			<ul>

			</ul>
		</div>
	<?php
	}
}
