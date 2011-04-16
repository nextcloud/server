<?php
/*
 * Template for admin pages
 */
?>
<h1>Administration</h1>
<h2>Users</h2>

<table id="userstable">
	<thead>
		<tr>
			<th>Name</th>
			<th>Groups</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_["users"] as $user): ?>
			<tr>
				<td><?php echo $user["name"]; ?></td>
				<td><?php echo $user["groups"]; ?></td>
				<td x-uid="<?php echo $user["name"] ?>"><a href="" class="edituser-button">edit</a> | <a  class="removeuser-button" href="">remove</a></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<a id="adduser-button" href="">New user</a>

<h2>Groups</h2>
<form>
	<table id="groupstable">
		<thead>
			<tr>
				<th>Name</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($_["groups"] as $group): ?>
				<tr>
					<td><?php echo $group["name"] ?></td>
					<td x-gid="<?php echo $group["name"]; ?>"><a class="removegroup-button" href="">remove</a></td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<td><input type="text" name="name" /></td>
				<td><input type="submit" /></td>
			</tr>
		</tbody>
	</table>
</form>

<a id="addgroup-button" href="">Add group</a>


<div id="adduser-form" title="Add user">
	<form id="createuserdata">
		<fieldset>
		User name<br>
		<input type="text" name="username" /><br>
		Password<br>
		<input type="password" name="password" />
		</fieldset>
		<fieldset id="usergroups">
		groups<br>
		<?php foreach($_["groups"] as $i): ?>
			<input type="checkbox" name="groups[]" value="<? echo $i["name"]; ?>" /><? echo $i["name"]; ?><br>
		<?php endforeach; ?>
		</fieldset>
	</form>
</div>

<div id="edituser-form" title="Force new password">
	<form id="edituserdata">
		New password for <span id="edituserusername">$user</span><br>
		<input type="password" name="password" />
		<input type="hidden" name="username" value="">
	</form>
</div>

<div id="removeuser-form" title="Remove user">
	<form id="removeuserdata">
		Do you really want to delete user <span id="deleteuserusername">$user</span>?
		<input type="hidden" name="username" value="">
	</form>
</div>

<div id="removegroup-form" title="Remove Group">
	<form id="removeuserdata">
		Do you really want to delete group <span id="deletegroupgroupname">$group</span>?
		<input id="deletegroupnamefield" type="hidden" name="username" value="">
	</form>
</div>
