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
script('settings', [ 'settings', 'admin', 'log', 'certificates'] );
script('core', ['multiselect', 'setupchecks']);
script('files', 'jquery.fileupload');
vendor_script('select2/select2');
vendor_style('select2/select2');

?>

<div id="app-navigation">
	<ul>
		<?php foreach($_['forms'] as $form) {
			if (isset($form['anchor'])) {
				$anchor = \OC::$server->getURLGenerator()->linkToRoute('settings.AdminSettings.index', ['section' => $form['anchor']]);
				$sectionName = $form['section-name'];
				$active = $form['active'] ? ' class="active"' : '';
				print_unescaped(sprintf("<li%s><a href='%s'>%s</a></li>", $active, \OCP\Util::sanitizeHTML($anchor), \OCP\Util::sanitizeHTML($sectionName)));
			}
		}?>
	</ul>
</div>

<div id="app-content">
	<?php print_unescaped($_['content']); ?>
</div>
