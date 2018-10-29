<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
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

/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<div id="<?php p($_['appid']); ?>" class="section workflowengine">
	<h2 class="inlineblock"><?php p($_['heading']); ?></h2>
	<?php if (!empty($_['docs'])): ?>
		<a target="_blank" rel="noreferrer noopener" class="icon-info svg"
		   title="<?php p($l->t('Open documentation'));?>"
		   href="<?php p(link_to_docs($_['docs'])); ?>">
		</a>
	<?php endif; ?>

	<?php if (!empty($_['settings-hint'])): ?>
		<p class="settings-hint"><?php p($_['settings-hint']); ?></p>
	<?php endif; ?>

	<?php if (!empty($_['description'])): ?>
		<p><?php p($_['description']); ?></p>
	<?php endif; ?>

	<div class="rules"><span class="icon-loading-small"></span> <?php p($l->t('Loading…')); ?></div>
</div>
