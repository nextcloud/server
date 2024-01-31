<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

style('settings', 'settings');
script('settings', 'settings');
\OCP\Util::addScript('settings', 'legacy-admin');
script('core', 'setupchecks');
script('files', 'jquery.fileupload');

?>

<div id="app-navigation">
	<?php if (!empty($_['forms']['admin'])): ?>
		<div id="app-navigation-caption-personal" class="app-navigation-caption"><?php p($l->t('Personal')); ?></div>
	<?php endif; ?>
	<nav class="app-navigation-personal" aria-labelledby="app-navigation-caption-personal">
		<ul>
			<?php foreach ($_['forms']['personal'] as $form) {
				if (isset($form['anchor'])) {
					$anchor = \OC::$server->getURLGenerator()->linkToRoute('settings.PersonalSettings.index', ['section' => $form['anchor']]);
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
					$anchor = \OC::$server->getURLGenerator()->linkToRoute('settings.AdminSettings.index', ['section' => $form['anchor']]);
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
<div id="app-content" <?php if (!empty($_['activeSectionId'])) { ?> data-active-section-id="<?php print_unescaped($_['activeSectionId']) ?>" <?php } if (!empty($_['activeSectionType'])) { ?> data-active-section-type="<?php print_unescaped($_['activeSectionType']) ?>" <?php } ?>>
	<?php print_unescaped($_['content']); ?>
</div>
