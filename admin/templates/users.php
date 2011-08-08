<fieldset>
	<legend><?php echo $l->t( 'Users' ); ?></legend>
	<table id="usertable">
		<thead>
			<tr>
				<th><?php echo $l->t( 'Name' ); ?></th>
				<th><?php echo $l->t( 'Groups' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<tr id="createuserform">
				<form id="createuserdata">
					<td>
						<input x-use="createuserfield" type="text" name="username" placeholder="<?php echo $l->t( 'Name' ); ?>" />
						<input x-use="createuserfield" type="password" name="password" placeholder="<?php echo $l->t( 'Password' ); ?>" />
					</td>
					<td id="createusergroups">
						<?php foreach($_["groups"] as $i): ?>
							<input id='newuser_group_<?php echo $i["name"]; ?>' x-use="createusercheckbox" x-gid="<?php echo $i["name"]; ?>" type="checkbox" name="groups[]" value="<?php echo $i["name"]; ?>" />
							<span x-gid="<?php echo $i["name"]; ?>"><label for='newuser_group_<?php echo $i["name"]; ?>'><?php echo $i["name"]; ?></label></span>
						<?php endforeach; ?>
					</td>
					<td>
						<input type="submit" id="createuserbutton" value="<?php echo $l->t( 'Add user' ); ?>" />
					</td>
				</form>
			</tr>
			<?php foreach($_["users"] as $user): ?>
				<tr x-uid="<?php echo $user["name"] ?>">
					<td x-use="username"><span x-use="usernamediv"><?php echo $user["name"]; ?></span></td>
					<td x-use="usergroups"><div x-use="usergroupsdiv"><?php if( $user["groups"] ){ echo $user["groups"]; }else{echo "&nbsp";} ?></div></td>
					<td>
						<?php if($user['name']!=OC_User::getUser()):?>
							<input type="submit" class="removeuserbutton" value="<?php echo $l->t( 'Remove' ); ?>" />
						<?php endif;?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</fieldset>

<fieldset>
	<legend><?php echo $l->t( 'Groups' ); ?></legend>
	<table id="grouptable">
		<tbody>
			<form id="creategroupdata">
				<tr>
					<td><input x-use="creategroupfield" type="text" name="groupname" placeholder="New group" /></td>
					<td><input type="submit" id="creategroupbutton" value="<?php echo $l->t( 'Create group' ); ?>" /></td>
				</tr>
			</form>
			<?php foreach($_["groups"] as $group): ?>
				<tr x-gid="<?php echo $group["name"]; ?>">
					<td><?php echo $group["name"] ?></td>
					<td>
						<?php if( $group["name"] != "admin" ): ?>
							<input type="submit" class="removegroupbutton" value="<?php echo $l->t( 'remove' ); ?>" />
						<?php else: ?>
							&nbsp;
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</fieldset>


<span id="changegroups" style="display:none">
	<form id="changegroupsform">
		<input id="changegroupuid" type="hidden" name="username" value="" />
		<input id="changegroupgid" type="hidden" name="group" value="" />
		<?php foreach($_["groups"] as $i): ?>
			<input x-use="togglegroup" x-gid="<?php echo $i["name"]; ?>" type="checkbox" name="groups[]" value="<?php echo $i["name"]; ?>" />
			<span x-use="togglegroup" x-gid="<?php echo $i["name"]; ?>"><?php echo $i["name"]; ?></span>
		<?php endforeach; ?>
	</form>
</span>

<span id="changepassword" style="display:none">
	<form id="changepasswordform">
		<input id="changepassworduid" type="hidden" name="username" value="" />
		<?php echo $l->t( 'Force new password:' ); ?>
		<input id="changepasswordpwd" type="password" name="password" value="" />
		<input type="submit" id="changepasswordbutton" value="<?php echo $l->t( 'Set' ); ?>" />
	</form>
</span>

<div id="removeuserform" title="Remove user">
	<form id="removeuserdata">
		<?php echo $l->t( 'Do you really want to delete user' ); ?> <span id="deleteuserusername">$user</span>?
		<input id="deleteusernamefield" type="hidden" name="username" value="">
	</form>
</div>

<div id="removegroupform" title="Remove Group">
	<form id="removegroupdata">
		<?php echo $l->t( 'Do you really want to delete group' ); ?> <span id="removegroupgroupname">$group</span>?
		<input id="removegroupnamefield" type="hidden" name="groupname" value="">
	</form>
</div>

<div id="errordialog" title="Error">
	<span id="errormessage"></span>
</div>
