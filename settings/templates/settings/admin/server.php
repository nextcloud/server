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

<div id="security-warning" class="section">
	<h2><?php p($l->t('Security & setup warnings'));?></h2>
	<p class="settings-hint"><?php p($l->t('It\'s important for the security and performance of your instance that everything is configured correctly. To help you with that we are doing some automatic checks. Please see the Tips & Tricks section and the documentation for more information.'));?></p>
	<ul>
		<?php
		// is php setup properly to query system environment variables like getenv('PATH')
		if ($_['getenvServerNotWorking']) {
			?>
			<li>
				<?php p($l->t('PHP does not seem to be setup properly to query system environment variables. The test with getenv("PATH") only returns an empty response.')); ?><br>
				<?php print_unescaped($l->t('Please check the <a target="_blank" rel="noreferrer noopener" href="%s">installation documentation ↗</a> for PHP configuration notes and the PHP configuration of your server, especially when using php-fpm.', link_to_docs('admin-php-fpm'))); ?>
			</li>
			<?php
		}

		// is read only config enabled
		if ($_['readOnlyConfigEnabled']) {
			?>
			<li>
				<?php p($l->t('The Read-Only config has been enabled. This prevents setting some configurations via the web-interface. Furthermore, the file needs to be made writable manually for every update.')); ?>
			</li>
			<?php
		}

		// Are doc blocks accessible?
		if (!$_['isAnnotationsWorking']) {
			?>
			<li>
				<?php p($l->t('PHP is apparently set up to strip inline doc blocks. This will make several core apps inaccessible.')); ?><br>
				<?php p($l->t('This is probably caused by a cache/accelerator such as Zend OPcache or eAccelerator.')); ?>
			</li>
			<?php
		}

		// Is the Transaction isolation level READ_COMMITTED?
		if ($_['invalidTransactionIsolationLevel']) {
			?>
			<li>
				<?php p($l->t('Your database does not run with "READ COMMITTED" transaction isolation level. This can cause problems when multiple actions are executed in parallel.')); ?>
			</li>
			<?php
		}

		// Warning if memcache is outdated
		foreach ($_['OutdatedCacheWarning'] as $php_module => $data) {
			?>
			<li>
				<?php p($l->t('%1$s below version %2$s is installed, for stability and performance reasons it is recommended to update to a newer %1$s version.', $data)); ?>
			</li>
			<?php
		}

		// if module fileinfo available?
		if (!$_['has_fileinfo']) {
			?>
			<li>
				<?php p($l->t('The PHP module \'fileinfo\' is missing. It is strongly recommended to enable this module to get the best results with MIME type detection.')); ?>
			</li>
			<?php
		}

		// locking configured optimally?
		if ($_['fileLockingType'] === 'none') {
			?>
			<li>
				<?php print_unescaped($l->t('Transactional file locking is disabled, this might lead to issues with race conditions. Enable \'filelocking.enabled\' in config.php to avoid these problems. See the <a target="_blank" rel="noreferrer noopener" href="%s">documentation ↗</a> for more information.', link_to_docs('admin-transactional-locking'))); ?>
			</li>
			<?php
		}

		// is locale working ?
		if (!$_['isLocaleWorking']) {
			?>
			<li>
				<?php
				$locales = 'en_US.UTF-8/fr_FR.UTF-8/es_ES.UTF-8/de_DE.UTF-8/ru_RU.UTF-8/pt_BR.UTF-8/it_IT.UTF-8/ja_JP.UTF-8/zh_CN.UTF-8';
				p($l->t('System locale can not be set to a one which supports UTF-8.'));
				?>
				<br>
				<?php
				p($l->t('This means that there might be problems with certain characters in filenames.'));
				?>
				<br>
				<?php
				p($l->t('It is strongly proposed to install the required packages on your system to support one of the following locales: %s.', [$locales]));
				?>
			</li>
			<?php
		}

		if ($_['suggestedOverwriteCliUrl']) {
			?>
			<li>
				<?php p($l->t('If your installation is not installed at the root of the domain and uses system Cron, there can be issues with the URL generation. To avoid these problems, please set the "overwrite.cli.url" option in your config.php file to the webroot path of your installation (Suggested: "%s")', $_['suggestedOverwriteCliUrl'])); ?>
			</li>
			<?php
		}

		if ($_['cronErrors']) {
			?>
			<li>
				<?php p($l->t('It was not possible to execute the cron job via CLI. The following technical errors have appeared:')); ?>
				<br>
				<ol>
					<?php foreach(json_decode($_['cronErrors']) as $error) { if(isset($error->error)) {?>
						<li><?php p($error->error) ?> <?php p($error->hint) ?></li>
					<?php }};?>
				</ol>
			</li>
			<?php
		}
		?>
	</ul>

	<div id="postsetupchecks" data-check-wellknown="<?php if($_['checkForWorkingWellKnownSetup']) { p('true'); } else { p('false'); } ?>">
		<div class="loading"></div>
		<ul class="errors hidden"></ul>
		<ul class="warnings hidden"></ul>
		<ul class="info hidden"></ul>
		<p class="hint hidden">
			<?php print_unescaped($l->t('Please double check the <a target="_blank" rel="noreferrer noopener" href="%s">installation guides ↗</a>, and check for any errors or warnings in the <a href="%s">log</a>.', [link_to_docs('admin-install'), \OC::$server->getURLGenerator()->linkToRoute('settings.AdminSettings.index', ['section' => 'logging'])] )); ?>
		</p>
	</div>
	<div id="security-warning-state">
		<span class="hidden icon-checkmark"><?php p($l->t('All checks passed.'));?></span>
	</div>
</div>

<div class="section" id="backgroundjobs">
	<h2 class="inlineblock"><?php p($l->t('Background jobs'));?></h2>
	<p class="cronlog inlineblock">
		<?php if ($_['lastcron'] !== false):
			$relative_time = relative_modified_date($_['lastcron']);
			$absolute_time = OC_Util::formatDate($_['lastcron']);
			if (time() - $_['lastcron'] <= 3600): ?>
				<span class="status success"></span>
				<span class="crondate" title="<?php p($absolute_time);?>">
				<?php p($l->t("Last job ran %s.", [$relative_time]));?>
			</span>
			<?php else: ?>
				<span class="status error"></span>
				<span class="crondate" title="<?php p($absolute_time);?>">
				<?php p($l->t("Last job execution ran %s. Something seems wrong.", [$relative_time]));?>
			</span>
			<?php endif;
		else: ?>
			<span class="status error"></span>
			<?php p($l->t("Background job didn’t run yet!"));
		endif; ?>
	</p>
	<a target="_blank" rel="noreferrer noopener" class="icon-info"
	   title="<?php p($l->t('Open documentation'));?>"
	   href="<?php p(link_to_docs('admin-background-jobs')); ?>"></a>

	<p class="settings-hint"><?php p($l->t('For optimal performance it\'s important to configure background jobs correctly. For bigger instances \'Cron\' is the recommended setting. Please see the documentation for more information.'));?></p>
	<p>
		<input type="radio" name="mode" value="ajax" class="radio"
			   id="backgroundjobs_ajax" <?php if ($_['backgroundjobs_mode'] === "ajax") {
			print_unescaped('checked="checked"');
		} ?>>
		<label for="backgroundjobs_ajax">AJAX</label><br/>
		<em><?php p($l->t("Execute one task with each page loaded")); ?></em>
	</p>
	<p>
		<input type="radio" name="mode" value="webcron" class="radio"
			   id="backgroundjobs_webcron" <?php if ($_['backgroundjobs_mode'] === "webcron") {
			print_unescaped('checked="checked"');
		} ?>>
		<label for="backgroundjobs_webcron">Webcron</label><br/>
		<em><?php p($l->t("cron.php is registered at a webcron service to call cron.php every 15 minutes over HTTP.")); ?></em>
	</p>
	<p>
		<input type="radio" name="mode" value="cron" class="radio"
			   id="backgroundjobs_cron" <?php if ($_['backgroundjobs_mode'] === "cron") {
			print_unescaped('checked="checked"');
		}
		if (!$_['cli_based_cron_possible']) {
			print_unescaped('disabled');
		}?>>
		<label for="backgroundjobs_cron">Cron</label><br/>
		<em><?php p($l->t("Use system cron service to call the cron.php file every 15 minutes.")); ?>
			<?php if($_['cli_based_cron_possible']) {
				p($l->t('The cron.php needs to be executed by the system user "%s".', [$_['cli_based_cron_user']]));
			} else {
				print_unescaped(str_replace(
					['{linkstart}', '{linkend}'],
					['<a href="http://php.net/manual/en/book.posix.php">', ' ↗</a>'],
					$l->t('To run this you need the PHP POSIX extension. See {linkstart}PHP documentation{linkend} for more details.')
				));
		} ?></em>

	</p>
</div>

<div class="section">
	<!-- should be the last part, so Updater can follow if enabled (it has no heading therefore). -->
	<h2><?php p($l->t('Version'));?></h2>
	<p><strong><a href="<?php print_unescaped($theme->getBaseUrl()); ?>" rel="noreferrer noopener" target="_blank"><?php p($theme->getTitle()); ?></a> <?php p(OC_Util::getHumanVersion()) ?></strong></p>
</div>
