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

/** @var \OCP\IL10N $l */
/** @var array $_ */

?>

<div class="section" id="admin-tips">
	<h2><?php p($l->t('Tips & tricks'));?></h2>
	<ul>
		<?php
		// SQLite database performance issue
		if ($_['databaseOverload']) {
			?>
			<li>
				<?php p($l->t('SQLite is used as database. For larger installations we recommend to switch to a different database backend.')); ?><br>
				<?php p($l->t('Especially when using the desktop client for file syncing the use of SQLite is discouraged.')); ?><br>
				<?php print_unescaped($l->t('To migrate to another database use the command line tool: \'occ db:convert-type\', or see the <a target="_blank" rel="noreferrer" href="%s">documentation ↗</a>.', link_to_docs('admin-db-conversion') )); ?>
			</li>
		<?php } ?>
		<li><a target="_blank" rel="noreferrer" href="<?php p(link_to_docs('admin-backup')); ?>"><?php p($l->t('How to do backups'));?> ↗</a></li>
		<li><a target="_blank" rel="noreferrer" href="<?php p(link_to_docs('admin-monitoring')); ?>"><?php p($l->t('Advanced monitoring'));?> ↗</a></li>
		<li><a target="_blank" rel="noreferrer" href="<?php p(link_to_docs('admin-performance')); ?>"><?php p($l->t('Performance tuning'));?> ↗</a></li>
		<li><a target="_blank" rel="noreferrer" href="<?php p(link_to_docs('admin-config')); ?>"><?php p($l->t('Improving the config.php'));?> ↗</a></li>
		<li><a target="_blank" rel="noreferrer" href="<?php p(link_to_docs('developer-theming')); ?>"><?php p($l->t('Theming'));?> ↗</a></li>
		<li><a target="_blank" rel="noreferrer" href="<?php p(link_to_docs('admin-security')); ?>"><?php p($l->t('Hardening and security guidance'));?> ↗</a></li>
	</ul>
</div>
