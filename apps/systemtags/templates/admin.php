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

script('core', [
	'systemtags/systemtags',
	'systemtags/systemtagmodel',
	'systemtags/systemtagscollection',
]);

script('systemtags', 'admin');
style('systemtags', 'settings');

/** @var \OCP\IL10N $l */
?>

<form id="systemtags" class="section" data-systemtag-id="">
	<h2><?php p($l->t('Collaborative tags')); ?></h2>
	<p class="settings-hint"><?php p($l->t('Collaborative tags are available for all users. Restricted tags are visible to users but cannot be assigned by them. Invisible tags are for internal use, since users cannot see or assign them.')); ?></p>

	<input type="hidden" name="systemtag" id="systemtag" placeholder="<?php p($l->t('Select tag …')); ?>" />

	<h3 id="systemtag_create"><?php p($l->t('Create a new tag')); ?></h3>

	<div class="systemtag-input">
		<input type="text" id="systemtag_name" name="systemtag_name" placeholder="<?php p($l->t('Name')); ?>">

		<select id="systemtag_level">
			<option value="3"><?php p($l->t('Public')); ?></option>
			<option value="2"><?php p($l->t('Restricted')); ?></option>
			<option value="0"><?php p($l->t('Invisible')); ?></option>
		</select>

		<a id="systemtag_delete" class="hidden button"><span><?php p($l->t('Delete')); ?></span></a>
		<a id="systemtag_reset" class="button"><span><?php p($l->t('Reset')); ?></span></a>
		<a id="systemtag_submit" class="button"><span><?php p($l->t('Create')); ?></span></a>
	</div>

</form>
