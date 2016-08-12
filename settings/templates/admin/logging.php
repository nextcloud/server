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

$levels = ['Debug', 'Info', 'Warning', 'Error', 'Fatal'];
$levelLabels = [
	$l->t( 'Everything (fatal issues, errors, warnings, info, debug)' ),
	$l->t( 'Info, warnings, errors and fatal issues' ),
	$l->t( 'Warnings, errors and fatal issues' ),
	$l->t( 'Errors and fatal issues' ),
	$l->t( 'Fatal issues only' ),
];

?>

<div class="section" id="log-section">
	<h2><?php p($l->t('Log'));?></h2>
	<?php if ($_['showLog'] && $_['doesLogFileExist']): ?>
		<table id="log" class="grid">
			<?php foreach ($_['entries'] as $entry): ?>
				<tr>
					<td>
						<?php p($levels[$entry->level]);?>
					</td>
					<td>
						<?php p($entry->app);?>
					</td>
					<td class="log-message">
						<?php p($entry->message);?>
					</td>
					<td class="date">
						<?php if(is_int($entry->time)){
							p(OC_Util::formatDate($entry->time));
						} else {
							p($entry->time);
						}?>
					</td>
					<td><?php isset($entry->user) ? p($entry->user) : p('--') ?></td>
				</tr>
			<?php endforeach;?>
		</table>
		<p><?php p($l->t('What to log'));?> <select name='loglevel' id='loglevel'>
				<?php for ($i = 0; $i < 5; $i++):
					$selected = '';
					if ($i == $_['loglevel']):
						$selected = 'selected="selected"';
					endif; ?>
					<option value='<?php p($i)?>' <?php p($selected) ?>><?php p($levelLabels[$i])?></option>
				<?php endfor;?>
			</select></p>

		<?php if ($_['logFileSize'] > 0): ?>
			<a href="<?php print_unescaped(OC::$server->getURLGenerator()->linkToRoute('settings.LogSettings.download')); ?>" class="button" id="downloadLog"><?php p($l->t('Download logfile'));?></a>
		<?php endif; ?>
		<?php if ($_['entriesremain']): ?>
			<input id="moreLog" type="button" value="<?php p($l->t('More'));?>...">
			<input id="lessLog" type="button" value="<?php p($l->t('Less'));?>...">
		<?php endif; ?>
		<?php if ($_['logFileSize'] > (100 * 1024 * 1024)): ?>
			<br>
			<em>
				<?php p($l->t('The logfile is bigger than 100 MB. Downloading it may take some time!')); ?>
			</em>
		<?php endif; ?>
	<?php endif; ?>
</div>
