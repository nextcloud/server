<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
/**
 * @var array $_
 */
$levels = array('Debug', 'Info', 'Warning', 'Error', 'Fatal');
$levelLabels = array(
	$l->t( 'Everything (fatal issues, errors, warnings, info, debug)' ),
	$l->t( 'Info, warnings, errors and fatal issues' ),
	$l->t( 'Warnings, errors and fatal issues' ),
	$l->t( 'Errors and fatal issues' ),
	$l->t( 'Fatal issues only' ),
);

$mail_smtpauthtype = array(
	''	=> $l->t('None'),
	'LOGIN'	=> $l->t('Login'),
	'PLAIN'	=> $l->t('Plain'),
	'NTLM'	=> $l->t('NT LAN Manager'),
);

$mail_smtpsecure = array(
	''		=> $l->t('None'),
	'ssl'	=> $l->t('SSL'),
	'tls'	=> $l->t('TLS'),
);

$mail_smtpmode = array(
	'php',
	'smtp',
);
if ($_['sendmail_is_available']) {
	$mail_smtpmode[] = 'sendmail';
}
if ($_['mail_smtpmode'] == 'qmail') {
	$mail_smtpmode[] = 'qmail';
}

?>

<?php

// is ssl working ?
if (!$_['isConnectedViaHTTPS']) {
	?>
<div class="section">
	<h2><?php p($l->t('Security Warning'));?></h2>

	<span class="securitywarning">
		<?php p($l->t('You are accessing %s via HTTP. We strongly suggest you configure your server to require using HTTPS instead.', $theme->getTitle())); ?>
	</span>

</div>
<?php
}

// is htaccess working ?
if (!$_['htaccessworking']) {
	?>
<div class="section">
	<h2><?php p($l->t('Security Warning'));?></h2>

	<span class="securitywarning">
		<?php p($l->t('Your data directory and your files are probably accessible from the internet. The .htaccess file is not working. We strongly suggest that you configure your webserver in a way that the data directory is no longer accessible or you move the data directory outside the webserver document root.')); ?>
	</span>

</div>
<?php
}

// is WebDAV working ?
if (!$_['isWebDavWorking']) {
	?>
<div class="section">
	<h2><?php p($l->t('Setup Warning'));?></h2>

	<span class="securitywarning">
		<?php p($l->t('Your web server is not yet properly setup to allow files synchronization because the WebDAV interface seems to be broken.')); ?>
		<?php print_unescaped($l->t('Please double check the <a href="%s">installation guides</a>.', link_to_docs('admin-install'))); ?>
	</span>

</div>
<?php
}

// Are doc blocks accessible?
if (!$_['isAnnotationsWorking']) {
	?>
<div class="section">
	<h2><?php p($l->t('Setup Warning'));?></h2>

	<span class="securitywarning">
		<?php p($l->t('PHP is apparently setup to strip inline doc blocks. This will make several core apps inaccessible.')); ?>
		<?php p($l->t('This is probably caused by a cache/accelerator such as Zend OPcache or eAccelerator.')); ?>
	</span>

</div>
<?php
}

// SQLite database performance issue
if ($_['databaseOverload']) {
	?>
<div class="section">
	<h2><?php p($l->t('Database Performance Info'));?></h2>

	<p class="securitywarning">
		<?php p($l->t('SQLite is used as database. For larger installations we recommend to change this. To migrate to another database use the command line tool: \'occ db:convert-type\'')); ?>
	</p>

</div>
<?php
}

// if module fileinfo available?
if (!$_['has_fileinfo']) {
	?>
<div class="section">
	<h2><?php p($l->t('Module \'fileinfo\' missing'));?></h2>

		<span class="connectionwarning">
		<?php p($l->t('The PHP module \'fileinfo\' is missing. We strongly recommend to enable this module to get best results with mime-type detection.')); ?>
	</span>

</div>
<?php
}

// is PHP at least at 5.3.8?
if ($_['old_php']) {
	?>
	<div class="section">
		<h2><?php p($l->t('Your PHP version is outdated'));?></h2>

		<span class="connectionwarning">
		<?php p($l->t('Your PHP version is outdated. We strongly recommend to update to 5.3.8 or newer because older versions are known to be broken. It is possible that this installation is not working correctly.')); ?>
	</span>

	</div>
<?php
}

// is PHP charset set to UTF8?
if (!$_['isPhpCharSetUtf8']) {
	?>
	<div class="section">
		<h2><?php p($l->t('PHP charset is not set to UTF-8'));?></h2>

		<span class="connectionwarning">
		<?php p($l->t("PHP charset is not set to UTF-8. This can cause major issues with non-ASCII characters in file names. We highly recommend to change the value of 'default_charset' php.ini to 'UTF-8'.")); ?>
	</span>

	</div>
<?php
}

// is locale working ?
if (!$_['isLocaleWorking']) {
	?>
<div class="section">
	<h2><?php p($l->t('Locale not working'));?></h2>

		<span class="connectionwarning">
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
			p($l->t('We strongly suggest to install the required packages on your system to support one of the following locales: %s.', array($locales)));
			?>
	</span>

</div>
<?php
}

// is internet connection working ?
if (!$_['internetconnectionworking']) {
	?>
	<div class="section">
		<h2><?php p($l->t('Internet connection not working'));?></h2>

		<span class="connectionwarning">
		<?php p($l->t('This server has no working internet connection. This means that some of the features like mounting of external storage, notifications about updates or installation of 3rd party apps don´t work. Accessing files from remote and sending of notification emails might also not work. We suggest to enable internet connection for this server if you want to have all features.')); ?>
	</span>

	</div>
<?php
}

if ($_['suggestedOverwriteWebroot']) {
	?>
	<div class="section">
		<h2><?php p($l->t('URL generation in notification emails'));?></h2>

		<span class="connectionwarning">
		<?php p($l->t('If your installation is not installed in the root of the domain and uses system cron, there can be issues with the URL generation. To avoid these problems, please set the "overwritewebroot" option in your config.php file to the webroot path of your installation (Suggested: "%s")', $_['suggestedOverwriteWebroot'])); ?>
	</span>

	</div>
<?php
}
?>

<?php foreach ($_['forms'] as $form) {
	print_unescaped($form);
}
;?>

<div class="section" id="backgroundjobs">
	<h2 class="inlineblock"><?php p($l->t('Cron'));?></h2>
	<?php if ($_['cron_log']): ?>
	<p class="cronlog inlineblock">
		<?php if ($_['lastcron'] !== false):
			$human_time = OC_Util::formatDate($_['lastcron']) . " UTC";
			if (time() - $_['lastcron'] <= 3600): ?>
				<span class="cronstatus success"></span>
				<?php p($l->t("Last cron was executed at %s.", array($human_time)));
			else: ?>
				<span class="cronstatus error"></span>
				<?php p($l->t("Last cron was executed at %s. This is more than an hour ago, something seems wrong.", array($human_time)));
			endif;
		else: ?>
			<span class="cronstatus error"></span>
			<?php p($l->t("Cron was not executed yet!"));
		endif; ?>
	</p>
	<?php endif; ?>
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

<div class="section" id="shareAPI">
	<h2><?php p($l->t('Sharing'));?></h2>
		<p id="enable">
			<input type="checkbox" name="shareapi_enabled" id="shareAPIEnabled"
				   value="1" <?php if ($_['shareAPIEnabled'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="shareAPIEnabled"><?php p($l->t('Allow apps to use the Share API'));?></label><br/>
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_allow_links" id="allowLinks"
				   value="1" <?php if ($_['allowLinks'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="allowLinks"><?php p($l->t('Allow users to share via link'));?></label><br/>
		</p>

			<p id="publicLinkSettings" class="indent <?php if ($_['allowLinks'] !== 'yes' || $_['shareAPIEnabled'] === 'no') p('hidden'); ?>">
				<input type="checkbox" name="shareapi_enforce_links_password" id="enforceLinkPassword"
						   value="1" <?php if ($_['enforceLinkPassword']) print_unescaped('checked="checked"'); ?> />
				<label for="enforceLinkPassword"><?php p($l->t('Enforce password protection'));?></label><br/>
				<input type="checkbox" name="shareapi_allow_public_upload" id="allowPublicUpload"
				       value="1" <?php if ($_['allowPublicUpload'] == 'yes') print_unescaped('checked="checked"'); ?> />
				<label for="allowPublicUpload"><?php p($l->t('Allow public uploads'));?></label><br/>

				<input type="checkbox" name="shareapi_default_expire_date" id="shareapiDefaultExpireDate"
				       value="1" <?php if ($_['shareDefaultExpireDateSet'] === 'yes') print_unescaped('checked="checked"'); ?> />
				<label for="shareapiDefaultExpireDate"><?php p($l->t('Set default expiration date'));?></label><br/>

			</p>
				<p id="setDefaultExpireDate" class="double-indent <?php if ($_['allowLinks'] !== 'yes' || $_['shareDefaultExpireDateSet'] === 'no' || $_['shareAPIEnabled'] === 'no') p('hidden');?>">
					<?php p($l->t( 'Expire after ' )); ?>
					<input type="text" name='shareapi_expire_after_n_days' id="shareapiExpireAfterNDays" placeholder="<?php p('7')?>"
						   value='<?php p($_['shareExpireAfterNDays']) ?>' />
					<?php p($l->t( 'days' )); ?>
					<input type="checkbox" name="shareapi_enforce_expire_date" id="shareapiEnforceExpireDate"
						   value="1" <?php if ($_['shareEnforceExpireDate'] === 'yes') print_unescaped('checked="checked"'); ?> />
					<label for="shareapiEnforceExpireDate"><?php p($l->t('Enforce expiration date'));?></label><br/>
				</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_allow_resharing" id="allowResharing"
				   value="1" <?php if ($_['allowResharing'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="allowResharing"><?php p($l->t('Allow resharing'));?></label><br/>
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_only_share_with_group_members" id="onlyShareWithGroupMembers"
				   value="1" <?php if ($_['onlyShareWithGroupMembers']) print_unescaped('checked="checked"'); ?> />
			<label for="onlyShareWithGroupMembers"><?php p($l->t('Restrict users to only share with users in their groups'));?></label><br/>
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_allow_mail_notification" id="allowMailNotification"
				   value="1" <?php if ($_['allowMailNotification'] === 'yes') print_unescaped('checked="checked"'); ?> />
			<label for="allowMailNotification"><?php p($l->t('Allow users to send mail notification for shared files'));?></label><br/>
		</p>
		<p class="<?php if ($_['shareAPIEnabled'] === 'no') p('hidden');?>">
			<input type="checkbox" name="shareapi_exclude_groups" id="shareapiExcludeGroups"
			       value="1" <?php if ($_['shareExcludeGroups']) print_unescaped('checked="checked"'); ?> />
			<label for="shareapiExcludeGroups"><?php p($l->t('Exclude groups from sharing'));?></label><br/>
		</p>
			<p id="selectExcludedGroups" class="indent <?php if (!$_['shareExcludeGroups'] || $_['shareAPIEnabled'] === 'no') p('hidden'); ?>">
				<select
					class="groupsselect"
					id="excludedGroups" data-placeholder="groups"
					title="<?php p($l->t('Groups'))?>" multiple="multiple">
					<?php foreach($_["groups"] as $group): ?>
						<option value="<?php p($group['gid'])?>" <?php if($group['excluded']) { p('selected="selected"'); }?>><?php p($group['gid']);?></option>
					<?php endforeach;?>
				</select>
				<br />
				<em><?php p($l->t('These groups will still be able to receive shares, but not to initiate them.')); ?></em>
			</p>
</div>

<div class="section" id="security">
	<h2><?php p($l->t('Security'));?></h2>
	<p>
		<input type="checkbox" name="forcessl"  id="forcessl"
			<?php if ($_['enforceHTTPSEnabled']) {
				print_unescaped('checked="checked" ');
				print_unescaped('value="false"');
			}  else {
				print_unescaped('value="true"');
			}
			?>
			<?php if (!$_['isConnectedViaHTTPS']) p('disabled'); ?> />
		<label for="forcessl"><?php p($l->t('Enforce HTTPS'));?></label><br/>
		<em><?php p($l->t(
			'Forces the clients to connect to %s via an encrypted connection.',
			$theme->getName()
		)); ?></em>
		<?php if (!$_['isConnectedViaHTTPS']) {
			print_unescaped("<br/><em>");
			p($l->t(
				'Please connect to your %s via HTTPS to enable or disable the SSL enforcement.',
				$theme->getName()
			));
			print_unescaped("</em>");
		}
		?>
	</p>
</div>

<div class="section"><form id="mail_settings">
	<h2><?php p($l->t('Email Server'));?></h2>

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
			   value='<?php p($_['mail_from_address']) ?>' />
		@
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

		<input type="checkbox" name="mail_smtpauth" id="mail_smtpauth" value="1"
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

	<p id="mail_credentials" <?php if (!$_['mail_smtpauth'] || $_['mail_smtpmode'] != 'smtp') print_unescaped(' class="hidden"'); ?>>
		<label for="mail_smtpname"><?php p($l->t( 'Credentials' )); ?></label>
		<input type="text" name='mail_smtpname' id="mail_smtpname" placeholder="<?php p($l->t('SMTP Username'))?>"
			   value='<?php p($_['mail_smtpname']) ?>' />
		<input type="password" name='mail_smtppassword' id="mail_smtppassword"
			   placeholder="<?php p($l->t('SMTP Password'))?>" value='<?php p($_['mail_smtppassword']) ?>' />
	</p>

	<br />
	<em><?php p($l->t( 'Test email settings' )); ?></em>
	<input type="submit" name="sendtestemail" id="sendtestemail" value="<?php p($l->t( 'Send email' )); ?>"/>
	<span id="sendtestmail_msg" class="msg"></span>
</form></div>

<div class="section">
	<h2><?php p($l->t('Log'));?></h2>
	<?php p($l->t('Log level'));?> <select name='loglevel' id='loglevel'>
<?php for ($i = 0; $i < 5; $i++):
	$selected = '';
	if ($i == $_['loglevel']):
		$selected = 'selected="selected"';
	endif; ?>
		<option value='<?php p($i)?>' <?php p($selected) ?>><?php p($levelLabels[$i])?></option>
<?php endfor;?>
</select>
	<table id="log" class="grid">
		<?php foreach ($_['entries'] as $entry): ?>
		<tr>
			<td>
				<?php p($levels[$entry->level]);?>
			</td>
			<td>
				<?php p($entry->app);?>
			</td>
			<td>
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
	<?php if ($_['entriesremain']): ?>
	<input id="moreLog" type="button" value="<?php p($l->t('More'));?>...">
	<input id="lessLog" type="button" value="<?php p($l->t('Less'));?>...">
	<?php endif; ?>

</div>

<div class="section">
	<h2><?php p($l->t('Version'));?></h2>
	<strong><?php p($theme->getTitle()); ?></strong> <?php p(OC_Util::getHumanVersion()) ?>
<?php if (OC_Util::getEditionString() === ''): ?>
	<p>
		<?php print_unescaped($l->t('Developed by the <a href="http://ownCloud.org/contact" target="_blank">ownCloud community</a>, the <a href="https://github.com/owncloud" target="_blank">source code</a> is licensed under the <a href="http://www.gnu.org/licenses/agpl-3.0.html" target="_blank"><abbr title="Affero General Public License">AGPL</abbr></a>.')); ?>
	</p>
<?php endif; ?>
</div>

<div class="section credits-footer">
	<p><?php print_unescaped($theme->getShortFooter()); ?></p>
</div>
