<div id="controls">
	<form id="newuser">
		<input id="newusername" placeholder="<?php echo $l->t('Name')?>"></input>
		<input type="password" id="newuserpassword" placeholder="<?php echo $l->t('Password')?>"></input>
		<select id="newusergroups" data-placeholder="groups" title="<?php echo $l->t('Groups')?>" multiple="multiple">
			<?php foreach($_["groups"] as $group): ?>
				<option value="<?php echo $group['name'];?>"><?php echo $group['name'];?></option>
			<?php endforeach;?>
		</select>
		<input type="submit" value="<?php echo $l->t('Create')?>"></input>
	</form>
</div>
<?php
$allGroups=array();
foreach($_["groups"] as $group){
	$allGroups[]=$group['name'];
}
?>
<table data-groups="<?php echo implode(', ',$allGroups);?>">
	<?php foreach($_["users"] as $user): ?>
		<tr data-uid="<?php echo $user["name"] ?>">
			<td class="name"><?php echo $user["name"]; ?></td>
			<td class="password">
				<span>●●●●●●●</span>
				<img class="svg" src="<?php echo image_path('core','actions/rename.svg')?>"/>
			</td>
			<td class="groups">
				<select data-username="<?php echo $user['name'] ;?>" data-user-groups="<?php echo $user['groups'] ;?>" data-placeholder="groups" title="<?php echo $l->t('Groups')?>" multiple="multiple">
					<?php foreach($_["groups"] as $group): ?>
						<option value="<?php echo $group['name'];?>"><?php echo $group['name'];?></option>
					<?php endforeach;?>
				</select>
			</td>
			<td class="remove">
				<?php if($user['name']!=OC_User::getUser()):?>
					<img alt="Remove" title="<?php echo $l->t('Remove')?>" class='svg' src='<?php echo image_path('core','actions/delete.svg') ?>'/>
				<?php endif;?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>
