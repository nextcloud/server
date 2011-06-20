<h2>Users</h2>

<table id="usertable">
	<thead>
		<tr>
			<th><?php echo $l->t( 'Name' ); ?></th>
			<th><?php echo $l->t( 'Groups' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tfoot>
		<tr id="createuseroption">
			<td><button id="createuseroptionbutton"><?php echo $l->t( 'Add user' ); ?></button></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<form id="createuserdata">
			<tr id="createuserform" style="display:none;">
				<td>
					<?php echo $l->t( 'Name' ); ?> <input x-use="createuserfield" type="text" name="username" /><br>
					<?php echo $l->t( 'Password' ); ?> <input x-use="createuserfield" type="password" name="password" />
				</td>
				<td id="createusergroups">
					<?php foreach($_["groups"] as $i): ?>
						<input x-use="createusercheckbox" x-gid="<? echo $i["name"]; ?>" type="checkbox" name="groups[]" value="<? echo $i["name"]; ?>" />
						<span x-gid="<? echo $i["name"]; ?>"><? echo $i["name"]; ?><br></span>
					<?php endforeach; ?>
				</td>
				<td>
					<button id="createuserbutton"><?php echo $l->t( 'Create user' ); ?></button>
				</td>
			</tr>
		</form>
	</tfoot>
	<tbody>
		<?php foreach($_["users"] as $user): ?>
			<tr x-uid="<?php echo $user["name"] ?>">
				<td x-use="username"><div x-use="usernamediv"><?php echo $user["name"]; ?></div></td>
				<td x-use="usergroups"><div x-use="usergroupsdiv"><?php if( $user["groups"] ){ echo $user["groups"]; }else{echo "&nbsp";} ?></div></td>
				<td><a  class="removeuserbutton" href=""><?php echo $l->t( 'remove' ); ?></a></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<h2><?php echo $l->t( 'Groups' ); ?></h2>
<table id="grouptable">
	<thead>
		<tr>
			<th><?php echo $l->t( 'Name' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tfoot>
		<form id="creategroupdata">
			<tr>
				<td><input x-use="creategroupfield" type="text" name="groupname" /></td>
				<td><button id="creategroupbutton"><?php echo $l->t( 'Create group' ); ?></button></td>
			</tr>
		</form>
	</tfoot>
	<tbody>
		<?php foreach($_["groups"] as $group): ?>
			<tr x-gid="<?php echo $group["name"]; ?>">
				<td><?php echo $group["name"] ?></td>
				<td>
					<?php if( $group["name"] != "admin" ): ?>
						<a class="removegroupbutton" href=""><?php echo $l->t( 'remove' ); ?></a>
					<?php else: ?>
						&nbsp;
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>



<div id="changegroups" style="display:none">
	<form id="changegroupsform">
		<input id="changegroupuid" type="hidden" name="username" value="" />
		<input id="changegroupgid" type="hidden" name="group" value="" />
		<?php foreach($_["groups"] as $i): ?>
			<input x-use="togglegroup" x-gid="<? echo $i["name"]; ?>" type="checkbox" name="groups[]" value="<? echo $i["name"]; ?>" />
			<span x-use="togglegroup" x-gid="<? echo $i["name"]; ?>"><? echo $i["name"]; ?><br></span>
		<?php endforeach; ?>
	</form>
</div>

<div id="changepassword" style="display:none">
	<form id="changepasswordform">
		<input id="changepassworduid" type="hidden" name="username" value="" />
		<?php echo $l->t( 'Force new password:' ); ?>
		<input id="changepasswordpwd" type="password" name="password" value="" />
		<button id="changepasswordbutton"><?php echo $l->t( 'Set' ); ?></button>
	</form>
</div>

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
