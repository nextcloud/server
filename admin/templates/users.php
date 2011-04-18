<h2>Users</h2>

<table id="usertable">
	<thead>
		<tr>
			<th>Name</th>
			<th>Groups</th>
			<th></th>
		</tr>
	</thead>
	<tfoot>
		<tr id="createuseroption">
			<td><button id="createuseroptionbutton">Add user</button></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<form id="createuserdata">
			<tr id="createuserform" style="display:none;">
				<td>
					Name <input x-use="createuserfield" type="text" name="username" /><br>
					Password <input x-use="createuserfield" type="password" name="password" />
				</td>
				<td id="createusergroups">
					<?php foreach($_["groups"] as $i): ?>
						<input x-use="createusercheckbox" x-gid="<? echo $i["name"]; ?>" type="checkbox" name="groups[]" value="<? echo $i["name"]; ?>" />
						<span x-gid="<? echo $i["name"]; ?>"><? echo $i["name"]; ?><br></span>
					<?php endforeach; ?>
				</td>
				<td>
					<button id="createuserbutton">Create user</button>
				</td>
			</tr>
		</form>
	</tfoot>
	<tbody>
		<?php foreach($_["users"] as $user): ?>
			<tr x-uid="<?php echo $user["name"] ?>">
				<td x-use="username"><div x-use="usernamediv"><?php echo $user["name"]; ?></div></td>
				<td x-use="usergroups"><div x-use="usergroupsdiv"><?php if( $user["groups"] ){ echo $user["groups"]; }else{echo "&nbsp";} ?></div></td>
				<td><a  class="removeuserbutton" href="">remove</a></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<h2>Groups</h2>
<table id="grouptable">
	<thead>
		<tr>
			<th>Name</th>
			<th></th>
		</tr>
	</thead>
	<tfoot>
		<form id="creategroupdata">
			<tr>
				<td><input x-use="creategroupfield" type="text" name="groupname" /></td>
				<td><button id="creategroupbutton">Create group</button></td>
			</tr>
		</form>
	</tfoot>
	<tbody>
		<?php foreach($_["groups"] as $group): ?>
			<tr x-gid="<?php echo $group["name"]; ?>">
				<td><?php echo $group["name"] ?></td>
				<td><a class="removegroupbutton" href="">remove</a></td>
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
		Force new password:
		<input id="changepasswordpwd" type="password" name="password" value="" />
		<button id="changepasswordbutton">Set</button>
	</form>
</div>

<div id="removeuserform" title="Remove user">
	<form id="removeuserdata">
		Do you really want to delete user <span id="deleteuserusername">$user</span>?
		<input id="deleteusernamefield" type="hidden" name="username" value="">
	</form>
</div>

<div id="removegroupform" title="Remove Group">
	<form id="removegroupdata">
		Do you really want to delete group <span id="removegroupgroupname">$group</span>?
		<input id="removegroupnamefield" type="hidden" name="groupname" value="">
	</form>
</div>
