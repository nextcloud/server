<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
/**
 * @var array $_
 * @var \OCP\IL10N $l
 * @var OC_Defaults $theme
 */

style('settings', 'settings');
script('settings', [ 'settings', 'admin', 'log'] );
script('core', ['multiselect', 'setupchecks']);
vendor_script('select2/select2');
vendor_style('select2/select2');

$levels = ['Debug', 'Info', 'Warning', 'Error', 'Fatal'];
$levelLabels = [
	$l->t( 'Everything (fatal issues, errors, warnings, info, debug)' ),
	$l->t( 'Info, warnings, errors and fatal issues' ),
	$l->t( 'Warnings, errors and fatal issues' ),
	$l->t( 'Errors and fatal issues' ),
	$l->t( 'Fatal issues only' ),
];

$mail_smtpauthtype = [
	''	=> $l->t('None'),
	'LOGIN'	=> $l->t('Login'),
	'PLAIN'	=> $l->t('Plain'),
	'NTLM'	=> $l->t('NT LAN Manager'),
];

$mail_smtpsecure = [
	''		=> $l->t('None'),
	'ssl'	=> $l->t('SSL/TLS'),
	'tls'	=> $l->t('STARTTLS'),
];

$mail_smtpmode = [
	'php',
	'smtp',
];
if ($_['sendmail_is_available']) {
	$mail_smtpmode[] = 'sendmail';
}
if ($_['mail_smtpmode'] == 'qmail') {
	$mail_smtpmode[] = 'qmail';
}
?>

<div id="app-navigation">
	<ul>
		<?php foreach($_['forms'] as $form) {
			if (isset($form['anchor'])) {
				$anchor = '#' . $form['anchor'];
				$sectionName = $form['section-name'];
				print_unescaped(sprintf("<li><a href='%s'>%s</a></li>", \OCP\Util::sanitizeHTML($anchor), \OCP\Util::sanitizeHTML($sectionName)));
			}
		}?>
	</ul>
</div>

<div id="app-content">

<div id="security-warning" class="section">
	<h2><?php p($l->t('Security & setup warnings'));?></h2>
	<ul>
<?php
// is php setup properly to query system environment variables like getenv('PATH')
if ($_['getenvServerNotWorking']) {
?>
	<li>
		<?php p($l->t('php does not seem to be setup properly to query system environment variables. The test with getenv("PATH") only returns an empty response.')); ?><br>
		<?php print_unescaped($l->t('Please check the <a target="_blank" rel="noreferrer" href="%s">installation documentation ↗</a> for php configuration notes and the php configuration of your server, especially when using php-fpm.', link_to_docs('admin-php-fpm'))); ?>
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
		<?php p($l->t('PHP is apparently setup to strip inline doc blocks. This will make several core apps inaccessible.')); ?><br>
		<?php p($l->t('This is probably caused by a cache/accelerator such as Zend OPcache or eAccelerator.')); ?>
	</li>
<?php
}

// Is the Transaction isolation level READ_COMMITED?
if ($_['invalidTransactionIsolationLevel']) {
	?>
	<li>
		<?php p($l->t('Your database does not run with "READ COMMITED" transaction isolation level. This can cause problems when multiple actions are executed in parallel.')); ?>
	</li>
<?php
}

// Windows Warning
if ($_['WindowsWarning']) {
	?>
	<li>
		<?php p($l->t('Your server is running on Microsoft Windows. We highly recommend Linux for optimal user experience.')); ?>
	</li>
<?php
}

// Warning if memcache is outdated
foreach ($_['OutdatedCacheWarning'] as $php_module => $data) {
	?>
	<li>
		<?php p($l->t('%1$s below version %2$s is installed, for stability and performance reasons we recommend updating to a newer %1$s version.', $data)); ?>
	</li>
<?php
}

// if module fileinfo available?
if (!$_['has_fileinfo']) {
	?>
	<li>
		<?php p($l->t('The PHP module \'fileinfo\' is missing. We strongly recommend to enable this module to get best results with mime-type detection.')); ?>
	</li>
<?php
}

// locking configured optimally?
if ($_['fileLockingType'] === 'none') {
	?>
	<li>
		<?php print_unescaped($l->t('Transactional file locking is disabled, this might lead to issues with race conditions. Enable \'filelocking.enabled\' in config.php to avoid these problems. See the <a target="_blank" rel="noreferrer" href="%s">documentation ↗</a> for more information.', link_to_docs('admin-transactional-locking'))); ?>
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
			p($l->t('This means that there might be problems with certain characters in file names.'));
		?>
			<br>
			<?php
			p($l->t('We strongly suggest installing the required packages on your system to support one of the following locales: %s.', [$locales]));
			?>
	</li>
<?php
}

if ($_['suggestedOverwriteCliUrl']) {
	?>
	<li>
		<?php p($l->t('If your installation is not installed in the root of the domain and uses system cron, there can be issues with the URL generation. To avoid these problems, please set the "overwrite.cli.url" option in your config.php file to the webroot path of your installation (Suggested: "%s")', $_['suggestedOverwriteCliUrl'])); ?>
	</li>
<?php
}

if ($_['cronErrors']) {
	?>
	<li>
			<?php p($l->t('It was not possible to execute the cronjob via CLI. The following technical errors have appeared:')); ?>
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
		<?php print_unescaped($l->t('Please double check the <a target="_blank" rel="noreferrer" href="%s">installation guides ↗</a>, and check for any errors or warnings in the <a href="#log-section">log</a>.', link_to_docs('admin-install'))); ?>
	</p>
</div>
<div id="security-warning-state">
	<span class="hidden icon-checkmark"><?php p($l->t('All checks passed.'));?></span>
</div>
</div>

	<div class="section" id="shareAPI">
		<h2><?php p($l->t('Sharing'));?></h2>
		<a target="_blank"  el="noreferrer" class="icon-info svg"
			title="<?php p($l->t('Open documentation'));?>"
			href="<?php p(link_to_docs('admin-sharing')); ?>"></a>
		<p id="enable">
			<input type="checkbox" name="shareapi_enabled" id="shareAPIEnabled" class="checkbox"
				   value="1" <?php if ($_['shareAPIEnabled'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="shareAPIEnabled"><?php p($l->t('Allow apps to use the Share API'));?></label><br/>
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_allow_links" id="allowLinks" class="checkbox"
				   value="1" <?php if ($_['allowLinks'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="allowLinks"><?php p($l->t('Allow users to share via link'));?></label><br/>
		</p>

		<p id="publicLinkSettings" class="indent <?php if ($_['allowLinks'] !== 'yes' || $_['shareAPIEnabled'] === 'no') p('hidden'); ?>">
			<input type="checkbox" name="shareapi_allow_public_upload" id="allowPublicUpload" class="checkbox"
				   value="1" <?php if ($_['allowPublicUpload'] == 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="allowPublicUpload"><?php p($l->t('Allow public uploads'));?></label><br/>

			<input type="checkbox" name="shareapi_enforce_links_password" id="enforceLinkPassword" class="checkbox"
				   value="1" <?php if ($_['enforceLinkPassword']) print_unescaped('checked="checked"'); ?> />
			<label for="enforceLinkPassword"><?php p($l->t('Enforce password protection'));?></label><br/>

			<input type="checkbox" name="shareapi_default_expire_date" id="shareapiDefaultExpireDate" class="checkbox"
				   value="1" <?php if ($_['shareDefaultExpireDateSet'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="shareapiDefaultExpireDate"><?php p($l->t('Set default expiration date'));?></label><br/>

			<input type="checkbox" name="shareapi_allow_public_notification" id="allowPublicMailNotification" class="checkbox"
				   value="1" <?php if ($_['allowPublicMailNotification'] == 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="allowPublicMailNotification"><?php p($l->t('Allow users to send mail notification for shared files'));?></label><br/>

		</p>
		<p id="setDefaultExpireDate" class="double-indent <?php if ($_['allowLinks'] !== 'yes' || $_['shareDefaultExpireDateSet'] === 'no' || $_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<?php p($l->t( 'Expire after ' )); ?>
			<input type="text" name='shareapi_expire_after_n_days' id="shareapiExpireAfterNDays" placeholder="<?php p('7')?>"
				   value='<?php p($_['shareExpireAfterNDays']) ?>' />
			<?php p($l->t( 'days' )); ?>
			<input type="checkbox" name="shareapi_enforce_expire_date" id="shareapiEnforceExpireDate" class="checkbox"
				   value="1" <?php if ($_['shareEnforceExpireDate'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="shareapiEnforceExpireDate"><?php p($l->t('Enforce expiration date'));?></label><br/>
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_allow_resharing" id="allowResharing" class="checkbox"
				   value="1" <?php if ($_['allowResharing'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="allowResharing"><?php p($l->t('Allow resharing'));?></label><br/>
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_allow_group_sharing" id="allowGroupSharing" class="checkbox"
				   value="1" <?php if ($_['allowGroupSharing'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="allowGroupSharing"><?php p($l->t('Allow sharing with groups'));?></label><br />
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_only_share_with_group_members" id="onlyShareWithGroupMembers" class="checkbox"
				   value="1" <?php if ($_['onlyShareWithGroupMembers']) print_unescaped('checked="checked"'); ?> />
			<label for="onlyShareWithGroupMembers"><?php p($l->t('Restrict users to only share with users in their groups'));?></label><br/>
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_allow_mail_notification" id="allowMailNotification" class="checkbox"
				   value="1" <?php if ($_['allowMailNotification'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="allowMailNotification"><?php p($l->t('Allow users to send mail notification for shared files to other users'));?></label><br/>
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_exclude_groups" id="shareapiExcludeGroups" class="checkbox"
				   value="1" <?php if ($_['shareExcludeGroups']) print_unescaped('checked="checked"'); ?> />
			<label for="shareapiExcludeGroups"><?php p($l->t('Exclude groups from sharing'));?></label><br/>
		</p>
		<p id="selectExcludedGroups" class="indent <?php if (!$_['shareExcludeGroups'] || $_['shareAPIEnabled'] === 'no') p('hidden'); ?>">
			<input name="shareapi_exclude_groups_list" type="hidden" id="excludedGroups" value="<?php p($_['shareExcludedGroupsList']) ?>" style="width: 400px"/>
			<br />
			<em><?php p($l->t('These groups will still be able to receive shares, but not to initiate them.')); ?></em>
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_allow_share_dialog_user_enumeration" value="1" id="shareapi_allow_share_dialog_user_enumeration" class="checkbox"
				<?php if ($_['allowShareDialogUserEnumeration'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="shareapi_allow_share_dialog_user_enumeration"><?php p($l->t('Allow username autocompletion in share dialog. If this is disabled the full username needs to be entered.'));?></label><br />
		</p>

		<?php print_unescaped($_['fileSharingSettings']); ?>
	</div>

<?php print_unescaped($_['filesExternal']); ?>

<?php foreach($_['forms'] as $form) {
	if (isset($form['form'])) {?>
		<div id="<?php isset($form['anchor']) ? p($form['anchor']) : p('');?>"><?php print_unescaped($form['form']);?></div>
	<?php }
};?>

<div class="section" id="backgroundjobs">
	<h2 class="inlineblock"><?php p($l->t('Cron'));?></h2>
	<?php if ($_['cron_log']): ?>
	<p class="cronlog inlineblock">
		<?php if ($_['lastcron'] !== false):
			$relative_time = relative_modified_date($_['lastcron']);
			$absolute_time = OC_Util::formatDate($_['lastcron']);
			if (time() - $_['lastcron'] <= 3600): ?>
				<span class="status success"></span>
				<span class="crondate" original-title="<?php p($absolute_time);?>">
					<?php p($l->t("Last cron job execution: %s.", [$relative_time]));?>
				</span>
			<?php else: ?>
				<span class="status error"></span>
				<span class="crondate" original-title="<?php p($absolute_time);?>">
					<?php p($l->t("Last cron job execution: %s. Something seems wrong.", [$relative_time]));?>
				</span>
			<?php endif;
		else: ?>
			<span class="status error"></span>
			<?php p($l->t("Cron was not executed yet!"));
		endif; ?>
	</p>
	<?php endif; ?>
	<a target="_blank" rel="noreferrer" class="icon-info svg"
		title="<?php p($l->t('Open documentation'));?>"
		href="<?php p(link_to_docs('admin-background-jobs')); ?>"></a>

	<p>
				<input type="radio" name="mode" value="ajax"
					   id="backgroundjobs_ajax" <?php if ($_['backgroundjobs_mode'] === "ajax") {
					print_unescaped('checked="checked"');
				} ?>>
				<label for="backgroundjobs_ajax">AJAX</label><br/>
				<em><?php p($l->t("Execute one task with each page loaded")); ?></em>
	</p>
	<p>
				<input type="radio" name="mode" value="webcron"
					   id="backgroundjobs_webcron" <?php if ($_['backgroundjobs_mode'] === "webcron") {
					print_unescaped('checked="checked"');
				} ?>>
				<label for="backgroundjobs_webcron">Webcron</label><br/>
				<em><?php p($l->t("cron.php is registered at a webcron service to call cron.php every 15 minutes over http.")); ?></em>
	</p>
	<p>
				<input type="radio" name="mode" value="cron"
					   id="backgroundjobs_cron" <?php if ($_['backgroundjobs_mode'] === "cron") {
					print_unescaped('checked="checked"');
				} ?>>
				<label for="backgroundjobs_cron">Cron</label><br/>
				<em><?php p($l->t("Use system's cron service to call the cron.php file every 15 minutes.")); ?></em>
	</p>
</div>

<div class="section" id='encryptionAPI'>
	<h2><?php p($l->t('Server-side encryption')); ?></h2>
	<a target="_blank" rel="noreferrer" class="icon-info svg"
		title="<?php p($l->t('Open documentation'));?>"
		href="<?php p(link_to_docs('admin-encryption')); ?>"></a>

	<p id="enable">
		<input type="checkbox"
			   id="enableEncryption" class="checkbox"
			   value="1" <?php if ($_['encryptionEnabled']) print_unescaped('checked="checked" disabled="disabled"'); ?> />
		<label
			for="enableEncryption"><?php p($l->t('Enable server-side encryption')); ?> <span id="startmigration_msg" class="msg"></span> </label><br/>
	</p>

	<div id="EncryptionWarning" class="warning hidden">
		<p><?php p($l->t('Please read carefully before activating server-side encryption: ')); ?></p>
		<ul>
			<li><?php p($l->t('Once encryption is enabled, all files uploaded to the server from that point forward will be encrypted at rest on the server. It will only be possible to disable encryption at a later date if the active encryption module supports that function, and all pre-conditions (e.g. setting a recover key) are met.')); ?></li>
			<li><?php p($l->t('Encryption alone does not guarantee security of the system. Please see ownCloud documentation for more information about how the encryption app works, and the supported use cases.')); ?></li>
			<li><?php p($l->t('Be aware that encryption always increases the file size.')); ?></li>
			<li><?php p($l->t('It is always good to create regular backups of your data, in case of encryption make sure to backup the encryption keys along with your data.')); ?></li>
		</ul>

		<p><?php p($l->t('This is the final warning: Do you really want to enable encryption?')) ?> <input type="button"
			   id="reallyEnableEncryption"
			   value="<?php p($l->t("Enable encryption")); ?>" /></p>
	</div>

	<div id="EncryptionSettingsArea" class="<?php if (!$_['encryptionEnabled']) p('hidden'); ?>">
		<div id='selectEncryptionModules' class="<?php if (!$_['encryptionReady']) p('hidden'); ?>">
			<?php
			if (empty($_['encryptionModules'])) {
				p($l->t('No encryption module loaded, please enable an encryption module in the app menu.'));
			} else { ?>
				<h3><?php p($l->t('Select default encryption module:')) ?></h3>
				<fieldset id='encryptionModules'>
					<?php foreach ($_['encryptionModules'] as $id => $module): ?>
						<input type="radio" id="<?php p($id) ?>"
							   name="default_encryption_module"
							   value="<?php p($id) ?>"
							<?php if ($module['default']) {
								p('checked');
							} ?>>
						<label
							for="<?php p($id) ?>"><?php p($module['displayName']) ?></label>
						<br/>

						<?php if ($id === 'OC_DEFAULT_MODULE') print_unescaped($_['ocDefaultEncryptionModulePanel']); ?>
					<?php endforeach; ?>
				</fieldset>
			<?php } ?>
		</div>
		<div id="migrationWarning" class="<?php if ($_['encryptionReady']) p('hidden'); ?>">
			<?php
			if ($_['encryptionReady'] === false && $_['externalBackendsEnabled'] === true) {
				p($l->t('You need to migrate your encryption keys from the old encryption (ownCloud <= 8.0) to the new one. Please enable the "Default encryption module" and run \'occ encryption:migrate\''));
			} elseif ($_['encryptionReady'] === false && $_['externalBackendsEnabled'] === false) {
				p($l->t('You need to migrate your encryption keys from the old encryption (ownCloud <= 8.0) to the new one.')); ?>
				<input type="submit" name="startmigration" id="startmigration"
					   value="<?php p($l->t('Start migration')); ?>"/>
			<?php } ?>
		</div>
	</div>
</div>

<div class="section" id="mail_general_settings">
	<form id="mail_general_settings_form" class="mail_settings">
		<h2><?php p($l->t('Email server'));?></h2>
		<a target="_blank" rel="noreferrer" class="icon-info svg"
			title="<?php p($l->t('Open documentation'));?>"
			href="<?php p(link_to_docs('admin-email')); ?>"></a>

		<p><?php p($l->t('This is used for sending out notifications.')); ?> <span id="mail_settings_msg" class="msg"></span></p>

		<p>
			<label for="mail_smtpmode"><?php p($l->t( 'Send mode' )); ?></label>
			<select name='mail_smtpmode' id='mail_smtpmode'>
				<?php foreach ($mail_smtpmode as $smtpmode):
					$selected = '';
					if ($smtpmode == $_['mail_smtpmode']):
						$selected = 'selected="selected"';
					endif; ?>
					<option value='<?php p($smtpmode)?>' <?php p($selected) ?>><?php p($smtpmode) ?></option>
				<?php endforeach;?>
			</select>

			<label id="mail_smtpsecure_label" for="mail_smtpsecure"
				   <?php if ($_['mail_smtpmode'] != 'smtp') print_unescaped(' class="hidden"'); ?>>
				<?php p($l->t( 'Encryption' )); ?>
			</label>
			<select name="mail_smtpsecure" id="mail_smtpsecure"
					<?php if ($_['mail_smtpmode'] != 'smtp') print_unescaped(' class="hidden"'); ?>>
				<?php foreach ($mail_smtpsecure as $secure => $name):
					$selected = '';
					if ($secure == $_['mail_smtpsecure']):
						$selected = 'selected="selected"';
					endif; ?>
					<option value='<?php p($secure)?>' <?php p($selected) ?>><?php p($name) ?></option>
				<?php endforeach;?>
			</select>
		</p>

		<p>
			<label for="mail_from_address"><?php p($l->t( 'From address' )); ?></label>
			<input type="text" name='mail_from_address' id="mail_from_address" placeholder="<?php p($l->t('mail'))?>"
				   value='<?php p($_['mail_from_address']) ?>' />@
			<input type="text" name='mail_domain' id="mail_domain" placeholder="example.com"
				   value='<?php p($_['mail_domain']) ?>' />
		</p>

		<p id="setting_smtpauth" <?php if ($_['mail_smtpmode'] != 'smtp') print_unescaped(' class="hidden"'); ?>>
			<label for="mail_smtpauthtype"><?php p($l->t( 'Authentication method' )); ?></label>
			<select name='mail_smtpauthtype' id='mail_smtpauthtype'>
				<?php foreach ($mail_smtpauthtype as $authtype => $name):
					$selected = '';
					if ($authtype == $_['mail_smtpauthtype']):
						$selected = 'selected="selected"';
					endif; ?>
					<option value='<?php p($authtype)?>' <?php p($selected) ?>><?php p($name) ?></option>
				<?php endforeach;?>
			</select>

			<input type="checkbox" name="mail_smtpauth" id="mail_smtpauth" class="checkbox" value="1"
				   <?php if ($_['mail_smtpauth']) print_unescaped('checked="checked"'); ?> />
			<label for="mail_smtpauth"><?php p($l->t( 'Authentication required' )); ?></label>
		</p>

		<p id="setting_smtphost" <?php if ($_['mail_smtpmode'] != 'smtp') print_unescaped(' class="hidden"'); ?>>
			<label for="mail_smtphost"><?php p($l->t( 'Server address' )); ?></label>
			<input type="text" name='mail_smtphost' id="mail_smtphost" placeholder="smtp.example.com"
				   value='<?php p($_['mail_smtphost']) ?>' />
			:
			<input type="text" name='mail_smtpport' id="mail_smtpport" placeholder="<?php p($l->t('Port'))?>"
				   value='<?php p($_['mail_smtpport']) ?>' />
		</p>
	</form>
	<form class="mail_settings" id="mail_credentials_settings">
		<p id="mail_credentials" <?php if (!$_['mail_smtpauth'] || $_['mail_smtpmode'] != 'smtp') print_unescaped(' class="hidden"'); ?>>
			<label for="mail_smtpname"><?php p($l->t( 'Credentials' )); ?></label>
			<input type="text" name='mail_smtpname' id="mail_smtpname" placeholder="<?php p($l->t('SMTP Username'))?>"
				   value='<?php p($_['mail_smtpname']) ?>' />
			<input type="password" name='mail_smtppassword' id="mail_smtppassword" autocomplete="off"
				   placeholder="<?php p($l->t('SMTP Password'))?>" value='<?php p($_['mail_smtppassword']) ?>' />
			<input id="mail_credentials_settings_submit" type="button" value="<?php p($l->t('Store credentials')) ?>">
		</p>
	</form>

	<br />
	<em><?php p($l->t( 'Test email settings' )); ?></em>
	<input type="submit" name="sendtestemail" id="sendtestemail" value="<?php p($l->t( 'Send email' )); ?>"/>
	<span id="sendtestmail_msg" class="msg"></span>
</div>

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
		</tr>
		<?php endforeach;?>
	</table>
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

	<p><?php p($l->t('What to log'));?> <select name='loglevel' id='loglevel'>
	<?php for ($i = 0; $i < 5; $i++):
		$selected = '';
		if ($i == $_['loglevel']):
			$selected = 'selected="selected"';
		endif; ?>
			<option value='<?php p($i)?>' <?php p($selected) ?>><?php p($levelLabels[$i])?></option>
	<?php endfor;?>
	</select></p>
</div>

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

<?php if (!empty($_['updaterAppPanel'])): ?>
	<div id="updater"><?php print_unescaped($_['updaterAppPanel']); ?></div>
<?php endif; ?>

<div class="section">
	<h2><?php p($l->t('Version'));?></h2>
	<p><a href="<?php print_unescaped($theme->getBaseUrl()); ?>" rel="noreferrer" target="_blank"><?php p($theme->getTitle()); ?></a> <?php p(OC_Util::getHumanVersion()) ?></p>
	<p><?php include('settings.development.notice.php'); ?></p>
</div>




</div>
