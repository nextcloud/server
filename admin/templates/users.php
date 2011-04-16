<?php
/*
 * Template for admin pages
 */
?>
<h1>Administration</h1>
<h2>Users</h2>

<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Groups</th>
			<th></th>
		</tr>
	<thead>
	<tbody>
		<?php foreach($_["users"] as $user): ?>
			<tr>
				<td><?php echo $user["name"]; ?></td>
				<td><?php echo $user["groups"]; ?></td>
				<td><a href="" class="edituser-button">edit</a> | <a  class="removeuser-button" href="">remove</a></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<a id="adduser-button" href="">New user</a>

<h2>Groups</h2>
<form>
	<table>
		<thead>
			<tr>
				<th>Name</th>
				<th></th>
			</tr>
		<thead>
		<tbody>
			<?php foreach($_["groups"] as $group): ?>
				<tr>
					<td><?php echo $group["name"] ?></td>
					<td><a  class="removegroup-button" href="">remove</a></td>
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
	<form>
		User name<br>
		<input type="text" name="name" /><br>
		Password<br>
		<input type="password" name="password" />
	</form>
</div>

<div id="edituser-form" title="Force new password">
	<form>
		New password for $user<br>
		<input type="password" name="password" />
	</form>
</div>

<div id="removeuser-form" title="Remove user">
	Do you really want to delete user $user?
</div>

<div id="removegroup-form" title="Remove Group">
	Do you really want to delete group $group?
</div>
