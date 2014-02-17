<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
$allGroups=array();
foreach($_["groups"] as $group) {
	$allGroups[] = $group['name'];
}
$_['subadmingroups'] = $allGroups;
$items = array_flip($_['subadmingroups']);
unset($items['admin']);
$_['subadmingroups'] = array_flip($items);
?>

<!-- THE APP NAVIGATION LEFT CONTENT AREA -->
<div id="app-navigation">
	<ul>
		<!-- Add new group -->
		<li>
			<form id="newgroup">
				<input type="text" id="newgroupname" placeholder="<?php p($l->t('Group')); ?>..." />
				<input type="submit" class="button" value="<?php p($l->t('Create'))?>" />
			</form>
		</li>
		<!-- Everyone -->
		<li>
			<a href="#"><?php p($l->t('Everyone')); ?></a>
		</li>

		<!-- The Admin Group -->
		<?php foreach($_["adminGroup"] as $adminGroup): ?>
			<li>
				<a href="#"><?php p($l->t('Admins')); ?></a>
				<span class="utils">
					<span class="usercount"><?php p(count($adminGroup['useringroup'])); ?></span>
				</span>
			</li>
		<?php endforeach; ?>

		<!--List of Groups-->
		<?php foreach($_["groups"] as $group): ?>
		<li data-gid="<?php p($group['name']) ?>">
			<a href="#"><?php p($group['name']); ?></a>
			<span class="utils">
				<span class="usercount"><?php p(count($group['useringroup'])); ?></span>
				<a href="#" class="action delete" original-title="<?php p($l->t('Delete'))?>">
					<img src="<?php print_unescaped(image_path('core', 'actions/delete.svg')) ?>" class="svg" />
				</a>
			</span>
		</li>
	<?php endforeach; ?>
	</ul>
	<!-- Default storage -->
	<div class="app-settings">
		<div class="quota">
			<span><?php p($l->t('Default Quota'));?></span>
			<?php if((bool) $_['isadmin']): ?>
			<select class='quota' data-inputtitle="<?php p($l->t('Please enter storage quota (ex: "512 MB" or "12 GB")')) ?>">
				<option
					<?php if($_['default_quota'] === 'none') print_unescaped('selected="selected"');?>
						value='none'>
					<?php p($l->t('Unlimited'));?>
				</option>
				<?php foreach($_['quota_preset'] as $preset):?>
				<?php if($preset !== 'default'):?>
				<option
				<?php if($_['default_quota']==$preset) print_unescaped('selected="selected"');?>
					value='<?php p($preset);?>'>
					<?php p($preset);?>
				</option>
				<?php endif;?>
				<?php endforeach;?>
				<?php if($_['defaultQuotaIsUserDefined']):?>
				<option selected="selected"
					value='<?php p($_['default_quota']);?>'>
					<?php p($_['default_quota']);?>
				</option>
				<?php endif;?>
				<option data-new value='other'>
					<?php p($l->t('Other'));?>
					...
				</option>
			</select>
			<?php endif; ?>
			<?php if((bool) !$_['isadmin']): ?>
				<select class='quota' disabled="disabled">
					<option selected="selected">
				<?php p($_['default_quota']);?>
					</option>
				</select>
			<?php endif; ?>
		</div>
	</div>
</div>
<div id="user-controls">
	<form id="newuser" autocomplete="off">
		<input id="newusername" type="text" placeholder="<?php p($l->t('Login Name'))?>" /> <input
			type="password" id="newuserpassword"
			placeholder="<?php p($l->t('Password'))?>" /> <select
			class="groupsselect"
			id="newusergroups" data-placeholder="groups"
			title="<?php p($l->t('Groups'))?>" multiple="multiple">
			<?php foreach($_["adminGroup"] as $adminGroup): ?>
			<option value="<?php p($adminGroup['name']);?>"><?php p($adminGroup['name']); ?></option>
			<?php endforeach; ?>
			<?php foreach($_["groups"] as $group): ?>
			<option value="<?php p($group['name']);?>"><?php p($group['name']);?></option>
			<?php endforeach;?>
		</select>
		<input type="submit" class="button" value="<?php p($l->t('Create'))?>" />
	</form>
	<?php if((bool)$_['recoveryAdminEnabled']): ?>
	<div class="recoveryPassword">
	<input id="recoveryPassword"
		   type="password"
		   placeholder="<?php p($l->t('Admin Recovery Password'))?>"
		   title="<?php p($l->t('Enter the recovery password in order to recover the users files during password change'))?>"
		   alt="<?php p($l->t('Enter the recovery password in order to recover the users files during password change'))?>"/>
	</div>
	<?php endif; ?>
	<form autocomplete="off" id="usersearchform">
		<input type="text" class="input" placeholder="<?php p($l->t( 'Search by Username' )); ?>" />
	</form>
</div>
<table class="hascontrols grid" data-groups="<?php p(json_encode($allGroups));?>">
	<thead>
		<tr>
			<?php if ($_['enableAvatars']): ?>
			<th id='headerAvatar'></th>
			<?php endif; ?>
			<th id='headerName'><?php p($l->t('Username'))?></th>
			<th id="headerDisplayName"><?php p($l->t( 'Full Name' )); ?></th>
			<th id="headerPassword"><?php p($l->t( 'Password' )); ?></th>
			<th id="headerGroups"><?php p($l->t( 'Groups' )); ?></th>
			<?php if(is_array($_['subadmins']) || $_['subadmins']): ?>
			<th id="headerSubAdmins"><?php p($l->t('Group Admin')); ?></th>
			<?php endif;?>
			<th id="headerQuota"><?php p($l->t('Storage')); ?></th>
			<th id="headerRemove">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_["users"] as $user): ?>
		<tr data-uid="<?php p($user["name"]) ?>"
			data-displayName="<?php p($user["displayName"]) ?>">
			<?php if ($_['enableAvatars']): ?>
			<td class="avatar"><div class="avatardiv"></div></td>
			<?php endif; ?>
			<td class="name"><?php p($user["name"]); ?></td>
			<td class="displayName"><span><?php p($user["displayName"]); ?></span> <img class="svg action"
				src="<?php p(image_path('core', 'actions/rename.svg'))?>"
				alt="<?php p($l->t("change full name"))?>" title="<?php p($l->t("change full name"))?>"/>
			</td>
			<td class="password"><span>●●●●●●●</span> <img class="svg action"
				src="<?php print_unescaped(image_path('core', 'actions/rename.svg'))?>"
				alt="<?php p($l->t("set new password"))?>" title="<?php p($l->t("set new password"))?>"/>
			</td>
			<td class="groups"><select
				class="groupsselect"
				data-username="<?php p($user['name']) ;?>"
				data-user-groups="<?php p(json_encode($user['groups'])) ;?>"
				data-placeholder="groups" title="<?php p($l->t('Groups'))?>"
				multiple="multiple">
					<?php foreach($_["adminGroup"] as $adminGroup): ?>
					<option value="<?php p($adminGroup['name']);?>"><?php p($adminGroup['name']); ?></option>
					<?php endforeach; ?>
					<?php foreach($_["groups"] as $group): ?>
					<option value="<?php p($group['name']);?>"><?php p($group['name']);?></option>
					<?php endforeach;?>
			</select>
			</td>
			<?php if(is_array($_['subadmins']) || $_['subadmins']): ?>
			<td class="subadmins"><select
				class="subadminsselect"
				data-username="<?php p($user['name']) ;?>"
				data-subadmin="<?php p(json_encode($user['subadmin']));?>"
				data-placeholder="subadmins" title="<?php p($l->t('Group Admin'))?>"
				multiple="multiple">
					<?php foreach($_["subadmingroups"] as $group): ?>
					<option value="<?php p($group);?>"><?php p($group);?></option>
					<?php endforeach;?>
			</select>
			</td>
			<?php endif;?>
			<td class="quota">
				<select class='quota-user' data-inputtitle="<?php p($l->t('Please enter storage quota (ex: "512 MB" or "12 GB")')) ?>">
					<option
						<?php if($user['quota'] === 'default') print_unescaped('selected="selected"');?>
							value='default'>
						<?php p($l->t('Default'));?>
					</option>
					<option
					<?php if($user['quota'] === 'none') print_unescaped('selected="selected"');?>
							value='none'>
						<?php p($l->t('Unlimited'));?>
					</option>
					<?php foreach($_['quota_preset'] as $preset):?>
					<option
					<?php if($user['quota']==$preset) print_unescaped('selected="selected"');?>
						value='<?php p($preset);?>'>
						<?php p($preset);?>
					</option>
					<?php endforeach;?>
					<?php if($user['isQuotaUserDefined']):?>
					<option selected="selected" value='<?php p($user['quota']);?>'>
						<?php p($user['quota']);?>
					</option>
					<?php endif;?>
					<option value='other' data-new>
						<?php p($l->t('Other'));?>
						...
					</option>
				</select>
			</td>
			<td class="remove">
				<?php if($user['name']!=OC_User::getUser()):?>
					<a href="#" class="action delete" original-title="<?php p($l->t('Delete'))?>">
						<img src="<?php print_unescaped(image_path('core', 'actions/delete.svg')) ?>" class="svg" />
					</a>
				<?php endif;?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
