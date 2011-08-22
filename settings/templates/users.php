<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$allGroups=array();
foreach($_["groups"] as $group) {
	$allGroups[]=$group['name'];
}
?>

<table data-groups="<?php echo implode(', ',$allGroups);?>">
	<thead id="controls">
		<tr><form id="newuser">
			<th class="name"><input id="newusername" placeholder="<?php echo $l->t('Name')?>" /></th>
			<th class="password"><input type="password" id="newuserpassword" placeholder="<?php echo $l->t('Password')?>" /></th>
			<th class="groups"><select id="newusergroups" data-placeholder="groups" title="<?php echo $l->t('Groups')?>" multiple="multiple">
			<?php foreach($_["groups"] as $group): ?>
				<option value="<?php echo $group['name'];?>"><?php echo $group['name'];?></option>
			<?php endforeach;?>
			</select></th>
			<th class="quota"></th>
			<th><input type="submit" value="<?php echo $l->t('Create')?>" /></th>
		</form></tr>
	</thead>
	<tbody>
	<?php foreach($_["users"] as $user): ?>
		<tr data-uid="<?php echo $user["name"] ?>">
			<td class="name"><?php echo $user["name"]; ?></td>
			<td class="password">
				<span>●●●●●●●</span>
				<img class="svg action" src="<?php echo image_path('core','actions/rename.svg')?>" alt="set new password" title="set new password" />
			</td>
			<td class="groups">
				<select data-username="<?php echo $user['name'] ;?>" data-user-groups="<?php echo $user['groups'] ;?>" data-placeholder="groups" title="<?php echo $l->t('Groups')?>" multiple="multiple">
					<?php foreach($_["groups"] as $group): ?>
						<option value="<?php echo $group['name'];?>"><?php echo $group['name'];?></option>
					<?php endforeach;?>
				</select>
			</td>
			<td class="quota" data-quota="<?php echo $user['quota']?>">
				<span><?php echo ($user['quota']>0)?$user['quota']:'None';?></span>
				<img class="svg action" src="<?php echo image_path('core','actions/rename.svg')?>" alt="set new password" title="set quota" />
			</td>
			<td class="remove">
				<?php if($user['name']!=OC_User::getUser()):?>
					<img alt="Delete" title="<?php echo $l->t('Delete')?>" class="svg action" src="<?php echo image_path('core','actions/delete.svg') ?>" />
				<?php endif;?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
