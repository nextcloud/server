<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

style('settings', 'settings');
\OCP\Util::addScript('settings', 'settings', 'core');
\OCP\Util::addScript('settings', 'legacy-admin');

?>

<div id="app-navigation">
	<?php if (!empty($_['forms']['admin'])): ?>
		<div id="app-navigation-caption-personal" class="app-navigation-caption"><?php p($l->t('Personal')); ?></div>
	<?php endif; ?>
	<nav class="app-navigation-personal" aria-labelledby="app-navigation-caption-personal">
		<ul>
			<?php foreach ($_['forms']['personal'] as $form) {
				if (isset($form['anchor'])) {
					$anchor = \OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('settings.PersonalSettings.index', ['section' => $form['anchor']]);
					$class = 'nav-icon-' . $form['anchor'];
					$sectionName = $form['section-name']; ?>
					<li <?php print_unescaped($form['active'] ? ' class="active"' : ''); ?> data-section-id="<?php print_unescaped($form['anchor']); ?>" data-section-type="personal">
						<a href="<?php p($anchor); ?>"<?php print_unescaped($form['active'] ? ' aria-current="page"' : ''); ?>>
							<?php if (!empty($form['icon'])) { ?>
								<img alt="" src="<?php print_unescaped($form['icon']); ?>">
								<span><?php p($form['section-name']); ?></span>
							<?php } else { ?>
								<span class="no-icon"><?php p($form['section-name']); ?></span>
							<?php } ?>
						</a>
					</li>
					<?php
				}
			}
?>
		</ul>
	</nav>

	<?php if (!empty($_['forms']['admin'])): ?>
		<div id="app-navigation-caption-administration" class="app-navigation-caption"><?php p($l->t('Administration')); ?></div>
	<?php endif; ?>
	<nav class="app-navigation-administration" aria-labelledby="app-navigation-caption-administration">
		<ul>
			<?php foreach ($_['forms']['admin'] as $form) {
				if (isset($form['anchor'])) {
					$anchor = \OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('settings.AdminSettings.index', ['section' => $form['anchor']]);
					$class = 'nav-icon-' . $form['anchor'];
					$sectionName = $form['section-name']; ?>
					<li <?php print_unescaped($form['active'] ? ' class="active"' : ''); ?> data-section-id="<?php print_unescaped($form['anchor']); ?>" data-section-type="admin">
						<a href="<?php p($anchor); ?>"<?php print_unescaped($form['active'] ? ' aria-current="page"' : ''); ?>>
							<?php if (!empty($form['icon'])) { ?>
								<img alt="" src="<?php print_unescaped($form['icon']); ?>">
								<span><?php p($form['section-name']); ?></span>
							<?php } else { ?>
								<span class="no-icon"><?php p($form['section-name']); ?></span>
							<?php } ?>
						</a>
					</li>
					<?php
				}
			}
?>
		</ul>
	</nav>
</div>
<main id="app-content" <?php if (!empty($_['activeSectionId'])) { ?> data-active-section-id="<?php print_unescaped($_['activeSectionId']) ?>" <?php } if (!empty($_['activeSectionType'])) { ?> data-active-section-type="<?php print_unescaped($_['activeSectionType']) ?>" <?php } ?>>
	<?php print_unescaped($_['content']); ?>
</main>
