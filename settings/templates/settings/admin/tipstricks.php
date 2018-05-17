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
	<p class="settings-hint"><?php p($l->t('There are a lot of features and config switches available to optimally customize and use this instance. Here are some pointers for more information.')); ?></p>
	<ul>
		<?php
		// SQLite database performance issue
		if ($_['databaseOverload']) {
			?>
			<li>
				<?php p($l->t('SQLite is currently being used as the backend database. For larger installations we recommend that you switch to a different database backend.')); ?><br>
				<?php p($l->t('This is particularly recommended when using the desktop client for file synchronisation.')); ?><br>
				<?php print_unescaped($l->t('To migrate to another database use the command line tool: \'occ db:convert-type\', or see the <a target="_blank" rel="noreferrer noopener" href="%s">documentation ↗</a>.', link_to_docs('admin-db-conversion') )); ?>
			</li>
		<?php } ?>
		<li><a target="_blank" rel="noreferrer noopener" href="<?php p(link_to_docs('admin-backup')); ?>"><?php p($l->t('How to do backups'));?> ↗</a></li>
		<li><a target="_blank" rel="noreferrer noopener" href="<?php p(link_to_docs('admin-performance')); ?>"><?php p($l->t('Performance tuning'));?> ↗</a></li>
		<li><a target="_blank" rel="noreferrer noopener" href="<?php p(link_to_docs('admin-config')); ?>"><?php p($l->t('Improving the config.php'));?> ↗</a></li>
		<li><a target="_blank" rel="noreferrer noopener" href="<?php p(link_to_docs('developer-theming')); ?>"><?php p($l->t('Theming'));?> ↗</a></li>
		<li><a target="_blank" rel="noreferrer noopener" href="https://scan.nextcloud.com"><?php p($l->t('Check the security of your Nextcloud over our security scan'));?> ↗</a></li>
		<li><a target="_blank" rel="noreferrer noopener" href="<?php p(link_to_docs('admin-security')); ?>"><?php p($l->t('Hardening and security guidance'));?> ↗</a></li>
	</ul>
</div>
