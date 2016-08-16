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

<div class="section" id="shareAPI">
	<h2><?php p($l->t('Sharing'));?></h2>
	<a target="_blank" rel="noreferrer" class="icon-info"
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
</div>
