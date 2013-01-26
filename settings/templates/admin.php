<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
$levels = array('Debug', 'Info', 'Warning', 'Error', 'Fatal');
?>

<?php

// is htaccess working ?
if (!$_['htaccessworking']) {
	?>
<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Security Warning');?></strong></legend>

	<span class="securitywarning">
		<?php echo $l->t('Your data directory and your files are probably accessible from the internet. The .htaccess file that ownCloud provides is not working. We strongly suggest that you configure your webserver in a way that the data directory is no longer accessible or you move the data directory outside the webserver document root.'); ?>
	</span>

</fieldset>
<?php
}

// is locale working ?
if (!$_['islocaleworking']) {
	?>
<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Locale not working');?></strong></legend>

		<span class="connectionwarning">
		<?php echo $l->t('This ownCloud server can\'t set system locale to "en_US.UTF-8". This means that there might be problems with certain characters in file names. We strongly suggest to install the required packages on your system to support en_US.UTF-8.'); ?>
	</span>

</fieldset>
<?php
}

// is internet connection working ?
if (!$_['internetconnectionworking']) {
	?>
<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Internet connection not working');?></strong></legend>

		<span class="connectionwarning">
		<?php echo $l->t('This ownCloud server has no working internet connection. This means that some of the features like mounting of external storage, notifications about updates or installation of 3rd party apps don´t work. Accessing files from remote and sending of notification emails might also not work. We suggest to enable internet connection for this server if you want to have all features of ownCloud.'); ?>
	</span>

</fieldset>
<?php
}
?>

<?php foreach ($_['forms'] as $form) {
	echo $form;
}
;?>

<fieldset class="personalblock" id="backgroundjobs">
	<legend><strong><?php echo $l->t('Cron');?></strong></legend>
	<table class="nostyle">
		<tr>
			<td>
				<input type="radio" name="mode" value="ajax"
					   id="backgroundjobs_ajax" <?php if ($_['backgroundjobs_mode'] == "ajax") {
					echo 'checked="checked"';
				} ?>>
				<label for="backgroundjobs_ajax">AJAX</label><br/>
				<em><?php echo $l->t("Execute one task with each page loaded"); ?></em>
			</td>
		</tr>
		<tr>
			<td>
				<input type="radio" name="mode" value="webcron"
					   id="backgroundjobs_webcron" <?php if ($_['backgroundjobs_mode'] == "webcron") {
					echo 'checked="checked"';
				} ?>>
				<label for="backgroundjobs_webcron">Webcron</label><br/>
				<em><?php echo $l->t("cron.php is registered at a webcron service. Call the cron.php page in the owncloud root once a minute over http."); ?></em>
			</td>
		</tr>
		<tr>
			<td>
				<input type="radio" name="mode" value="cron"
					   id="backgroundjobs_cron" <?php if ($_['backgroundjobs_mode'] == "cron") {
					echo 'checked="checked"';
				} ?>>
				<label for="backgroundjobs_cron">Cron</label><br/>
				<em><?php echo $l->t("Use systems cron service. Call the cron.php file in the owncloud folder via a system cronjob once a minute."); ?></em>
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="personalblock" id="shareAPI">
	<legend><strong><?php echo $l->t('Sharing');?></strong></legend>
	<table class="shareAPI nostyle">
		<tr>
			<td id="enable">
				<input type="checkbox" name="shareapi_enabled" id="shareAPIEnabled"
					   value="1" <?php if ($_['shareAPIEnabled'] == 'yes') echo 'checked="checked"'; ?> />
				<label for="shareAPIEnabled"><?php echo $l->t('Enable Share API');?></label><br/>
				<em><?php echo $l->t('Allow apps to use the Share API'); ?></em>
			</td>
		</tr>
		<tr>
			<td <?php if ($_['shareAPIEnabled'] == 'no') echo 'style="display:none"';?>>
				<input type="checkbox" name="shareapi_allow_links" id="allowLinks"
					   value="1" <?php if ($_['allowLinks'] == 'yes') echo 'checked="checked"'; ?> />
				<label for="allowLinks"><?php echo $l->t('Allow links');?></label><br/>
				<em><?php echo $l->t('Allow users to share items to the public with links'); ?></em>
			</td>
		</tr>
		<tr>
			<td <?php if ($_['shareAPIEnabled'] == 'no') echo 'style="display:none"';?>>
				<input type="checkbox" name="shareapi_allow_resharing" id="allowResharing"
					   value="1" <?php if ($_['allowResharing'] == 'yes') echo 'checked="checked"'; ?> />
				<label for="allowResharing"><?php echo $l->t('Allow resharing');?></label><br/>
				<em><?php echo $l->t('Allow users to share items shared with them again'); ?></em>
			</td>
		</tr>
		<tr>
			<td <?php if ($_['shareAPIEnabled'] == 'no') echo 'style="display:none"';?>>
				<input type="radio" name="shareapi_share_policy" id="sharePolicyGlobal"
					   value="global" <?php if ($_['sharePolicy'] == 'global') echo 'checked="checked"'; ?> />
				<label for="sharePolicyGlobal"><?php echo $l->t('Allow users to share with anyone'); ?></label><br/>
				<input type="radio" name="shareapi_share_policy" id="sharePolicyGroupsOnly"
					   value="groups_only" <?php if ($_['sharePolicy'] == 'groups_only') echo 'checked="checked"'; ?> />
				<label for="sharePolicyGroupsOnly"><?php echo $l->t('Allow users to only share with users in their groups');?></label><br/>
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="personalblock" id="security">
	<legend><strong><?php echo $l->t('Security');?></strong></legend>
	<table class="nostyle">
		<tr>
			<td id="enable">
				<input type="checkbox" name="forcessl"  id="enforceHTTPSEnabled"
					<?php if ($_['enforceHTTPSEnabled']) {
						echo 'checked="checked" ';
						echo 'value="false"';
					}  else {
						echo 'value="true"';
					}
					?>
					<?php if (!$_['isConnectedViaHTTPS']) echo 'disabled'; ?> />
				<label for="forcessl"><?php echo $l->t('Enforce HTTPS');?></label><br/>
				<em><?php echo $l->t('Enforces the clients to connect to ownCloud via an encrypted connection.'); ?></em>
				<?php if (!$_['isConnectedViaHTTPS']) {
					echo "<br/><em>";
					echo $l->t('Please connect to this ownCloud instance via HTTPS to enable or disable the SSL enforcement.');
					echo "</em>";
				}
				?>
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Log');?></strong></legend>
	<?php echo $l->t('Log level');?> <select name='loglevel' id='loglevel'>
	<option value='<?php echo $_['loglevel']?>'><?php echo $levels[$_['loglevel']]?></option>
	<?php for ($i = 0; $i < 5; $i++):
	if ($i != $_['loglevel']):?>
		<option value='<?php echo $i?>'><?php echo $levels[$i]?></option>
		<?php endif;
endfor;?>
</select>
	<table id='log'>
		<?php foreach ($_['entries'] as $entry): ?>
		<tr>
			<td>
				<?php echo $levels[$entry->level];?>
			</td>
			<td>
				<?php echo $entry->app;?>
			</td>
			<td>
				<?php echo $entry->message;?>
			</td>
			<td>
				<?php echo OC_Util::formatDate($entry->time);?>
			</td>
		</tr>
		<?php endforeach;?>
	</table>
	<?php if ($_['entriesremain']): ?>
	<input id='moreLog' type='button' value='<?php echo $l->t('More');?>...'>
	<?php endif; ?>

</fieldset>


<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Version');?></strong></legend>
	<strong>ownCloud</strong> <?php echo(OC_Util::getVersionString()); ?> <?php echo(OC_Util::getEditionString()); ?>
	(<?php echo(OC_Updater::ShowUpdatingHint()); ?>)<br/>
	<?php echo $l->t('Developed by the <a href="http://ownCloud.org/contact" target="_blank">ownCloud community</a>, the <a href="https://github.com/owncloud" target="_blank">source code</a> is licensed under the <a href="http://www.gnu.org/licenses/agpl-3.0.html" target="_blank"><abbr title="Affero General Public License">AGPL</abbr></a>.'); ?>
</fieldset>

