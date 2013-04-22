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

<script type="text/javascript" src="<?php print_unescaped(OC_Helper::linkToRoute('isadmin'));?>"></script>

<div id="controls">
	<form id="newuser" autocomplete="off">
		<input id="newusername" type="text" placeholder="<?php p($l->t('Login Name'))?>" /> <input
			type="password" id="newuserpassword"
			placeholder="<?php p($l->t('Password'))?>" /> <select
			class="groupsselect"
			id="newusergroups" data-placeholder="groups"
			title="<?php p($l->t('Groups'))?>" multiple="multiple">
			<?php foreach($_["groups"] as $group): ?>
			<option value="<?php p($group['name']);?>">
				<?php p($group['name']);?>
			</option>
			<?php endforeach;?>
		</select> <input type="submit" value="<?php p($l->t('Create'))?>" />
	</form>
	<div class="quota">
		<span><?php p($l->t('Default Storage'));?></span>
			<?php if((bool) $_['isadmin']): ?>
			<select class='quota'>
				<option
					<?php if($_['default_quota']=='none') print_unescaped('selected="selected"');?>
						value='none'>
					<?php p($l->t('Unlimited'));?>
				</option>
				<?php foreach($_['quota_preset'] as $preset):?>
				<?php if($preset!='default'):?>
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

<table class="hascontrols" data-groups="<?php p(implode(', ', $allGroups));?>">
	<thead>
		<tr>
			<th id='headerName'><?php p($l->t('Login Name'))?></th>
			<th id="headerDisplayName"><?php p($l->t( 'Display Name' )); ?></th>
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
			<td class="name"><?php p($user["name"]); ?></td>
			<td class="displayName"><span><?php p($user["displayName"]); ?></span> <img class="svg action"
				src="<?php p(image_path('core', 'actions/rename.svg'))?>"
				alt="<?php p($l->t("change display name"))?>" title="<?php p($l->t("change display name"))?>"/>
			</td>
			<td class="password"><span>●●●●●●●</span> <img class="svg action"
				src="<?php print_unescaped(image_path('core', 'actions/rename.svg'))?>"
				alt="<?php p($l->t("set new password"))?>" title="<?php p($l->t("set new password"))?>"/>
			</td>
			<td class="groups"><select
				class="groupsselect"
				data-username="<?php p($user['name']) ;?>"
				data-user-groups="<?php p($user['groups']) ;?>"
				data-placeholder="groups" title="<?php p($l->t('Groups'))?>"
				multiple="multiple">
					<?php foreach($_["groups"] as $group): ?>
					<option value="<?php p($group['name']);?>">
						<?php p($group['name']);?>
					</option>
					<?php endforeach;?>
			</select>
			</td>
			<?php if(is_array($_['subadmins']) || $_['subadmins']): ?>
			<td class="subadmins"><select
				class="subadminsselect"
				data-username="<?php p($user['name']) ;?>"
				data-subadmin="<?php p($user['subadmin']);?>"
				data-placeholder="subadmins" title="<?php p($l->t('Group Admin'))?>"
				multiple="multiple">
					<?php foreach($_["subadmingroups"] as $group): ?>
					<option value="<?php p($group);?>">
						<?php p($group);?>
					</option>
					<?php endforeach;?>
			</select>
			</td>
			<?php endif;?>
			<td class="quota">
				<select class='quota-user'>
					<option
						<?php if($user['quota']=='default') print_unescaped('selected="selected"');?>
							value='default'>
						<?php p($l->t('Default'));?>
					</option>
					<option
					<?php if($user['quota']=='none') print_unescaped('selected="selected"');?>
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
