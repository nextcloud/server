<?php
	script('updatenotification', 'admin');
	style('updatenotification', 'admin');

	/** @var array $_ */
	/** @var bool $isNewVersionAvailable */
	$isNewVersionAvailable = $_['isNewVersionAvailable'];
	/** @var string $newVersionString */
	$newVersionString = $_['newVersionString'];
	/** @var string $lastCheckedDate */
	$lastCheckedDate = $_['lastChecked'];
	/** @var array $channels */
	$channels = $_['channels'];
	/** @var string $currentChannel */
	$currentChannel = $_['currentChannel'];
?>
<form id="oca_updatenotification_section" class="followupsection">
	<?php if($isNewVersionAvailable === true) { ?>
		<strong><?php p($l->t('A new version is available: %s', [$newVersionString])); ?></strong>

		<?php if (!empty($_['versionIsEol'])) { ?>
			<p class="eol">
						<span class="warning">
							<span class="icon icon-error"></span>
							<?php p($l->t('The version you are running is not maintained anymore. Please make sure to update to a supported version as soon as possible.')); ?>
						</span>
			</p>
		<?php } ?>

		<?php if ($_['updaterEnabled']) { ?>
			<input type="button" id="oca_updatenotification_button" value="<?php p($l->t('Open updater')) ?>">
		<?php } ?>
		<?php if (!empty($_['downloadLink'])) { ?>
			<a href="<?php p($_['downloadLink']); ?>" class="button<?php if ($_['updaterEnabled']) { p(' hidden'); } ?>"><?php p($l->t('Download now')) ?></a>
		<?php } ?>
	<?php } else { ?>
		<?php p($l->t('Your version is up to date.')); ?>
		<span class="icon-info svg" title="<?php p($l->t('Checked on %s', [$lastCheckedDate])) ?>"></span>
	<?php } ?>

	<p>
		<label for="release-channel"><?php p($l->t('Update channel:')) ?></label>
		<select id="release-channel">
			<option value="<?php p($currentChannel); ?>"><?php p($currentChannel); ?></option>
			<?php foreach ($channels as $channel => $channelTitle){ ?>
				<option value="<?php p($channelTitle) ?>">
					<?php p($channelTitle) ?>
				</option>
			<?php } ?>
		</select>
		<span id="channel_save_msg" class="msg"></span>
	</p>
	<p>
		<em><?php p($l->t('You can always update to a newer version / experimental channel. But you can never downgrade to a more stable channel.')); ?></em>
		<em><?php p($l->t('Note that after a new release it can take some time before it shows up here. We roll out new versions spread out over time to our users and sometimes skip a version when issues are found.')); ?></em>
	</p>


	<p id="oca_updatenotification_groups">
		<br />
		<?php p($l->t('Notify members of the following groups about available updates:')); ?>
		<input name="oca_updatenotification_groups_list" type="hidden" id="oca_updatenotification_groups_list" value="<?php p($_['notify_groups']) ?>" style="width: 400px">
		<em class="<?php if (!in_array($currentChannel, ['daily', 'git'])) p('hidden'); ?>">
			<br />
			<?php p($l->t('Only notification for app updates are available.')); ?>
			<?php if ($currentChannel === 'daily') p($l->t('The selected update channel makes dedicated notifications for the server obsolete.')); ?>
			<?php if ($currentChannel === 'git') p($l->t('The selected update channel does not support updates of the server.')); ?>
		</em>
	</p>
</form>
