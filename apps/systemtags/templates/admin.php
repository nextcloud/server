<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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

vendor_script('core', 'select2/select2');
vendor_style('core', 'select2/select2');
script('core', [
	'oc-backbone-webdav',
	'systemtags/systemtags',
	'systemtags/systemtagmodel',
	'systemtags/systemtagscollection',
]);

script('systemtags', 'admin');

/** @var \OCP\IL10N $l */
?>

<form id="systemtags" class="section" data-systemtag-id="">
	<h2><?php p($l->t('Collaborative tags')); ?></h2>

	<input type="hidden" name="systemtag" id="systemtag" placeholder="<?php p($l->t('Select tag…')); ?>" style="width: 400px;" />

	<br><br>

	<input type="text" id="systemtag_name" name="systemtag_name" placeholder="<?php p($l->t('Name')); ?>" style="width: 200px;">

	<span id="systemtag_delete" class="hidden">
		<img src="<?php p(\OCP\Template::image_path('core', 'actions/delete.svg')); ?>" alt="<?php p($l->t('Delete')); ?>">
	</span>

	<br>

	<select id="systemtag_level">
		<option value="3"><?php p($l->t('Public')); ?></option>
		<option value="2"><?php p($l->t('Restricted')); ?></option>
		<option value="0"><?php p($l->t('Invisible')); ?></option>
	</select>

	<input type="button" id="systemtag_submit" value="<?php p($l->t('Create')); ?>">
	<input type="button" id="systemtag_reset" value="<?php p($l->t('Reset')); ?>">
</form>
