<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

script('settings', [
	'users/deleteHandler',
	'users/filter',
	'users/users',
	'users/groups'
]);
script('core', [
	'multiselect',
	'singleselect'
]);
style('settings', 'settings');

$userlistParams = array();
$allGroups=array();
foreach($_["groups"] as $group) {
	$allGroups[] = $group['name'];
}
foreach($_["adminGroup"] as $group) {
	$allGroups[] = $group['name'];
}
$userlistParams['subadmingroups'] = $allGroups;
$userlistParams['allGroups'] = json_encode($allGroups);
$items = array_flip($userlistParams['subadmingroups']);
unset($items['admin']);
$userlistParams['subadmingroups'] = array_flip($items);

translation('settings');
?>

<div id="app-navigation">
	<?php print_unescaped($this->inc('users/part.grouplist')); ?>
	<div id="app-settings">
		<div id="app-settings-header">
			<button class="settings-button" tabindex="0" data-apps-slide-toggle="#app-settings-content"></button>
		</div>
		<div id="app-settings-content">
			<?php print_unescaped($this->inc('users/part.setquota')); ?>

			<div id="userlistoptions">
				<p>
					<input type="checkbox" name="StorageLocation" value="StorageLocation" id="CheckboxStorageLocation">
					<label for="CheckboxStorageLocation">
						<?php p($l->t('Show storage location')) ?>
					</label>
				</p>
				<p>
					<input type="checkbox" name="LastLogin" value="LastLogin" id="CheckboxLastLogin">
					<label for="CheckboxLastLogin">
						<?php p($l->t('Show last log in')) ?>
					</label>
				</p>
				<p>
					<input type="checkbox" name="UserBackend" value="UserBackend" id="CheckboxUserBackend">
					<label for="CheckboxUserBackend">
						<?php p($l->t('Show user backend')) ?>
					</label>
				</p>
				<p>
					<input type="checkbox" name="MailOnUserCreate" value="MailOnUserCreate" id="CheckboxMailOnUserCreate">
					<label for="CheckboxMailOnUserCreate">
						<?php p($l->t('Send email to new user')) ?>
					</label>
				</p>
				<p>
					<input type="checkbox" name="EmailAddress" value="EmailAddress" id="CheckboxEmailAddress">
					<label for="CheckboxEmailAddress">
						<?php p($l->t('Show email address')) ?>
					</label>
				</p>
			</div>
		</div>
	</div>
</div>

<div id="app-content">
	<?php print_unescaped($this->inc('users/part.createuser')); ?>
	<?php print_unescaped($this->inc('users/part.userlist', $userlistParams)); ?>
</div>
