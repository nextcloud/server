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
			<th></th>
			<th>Name</th>
			<th>Groups</th>
		</tr>
	<thead>
	<tbody>
		<? foreach( $_["users"] as $user ){ ?>
			<tr>
				<td><input type="checkbox"></td>
				<td><? echo $user["name"] ?></td>
				<td><? echo $user["groups"] ?></td>
			</tr>
		<? } ?>
	</tbody>
</table>

<h2>Groups</h2>
<table>
	<thead>
		<tr>
			<th>Name</th>
			<th></th>
		</tr>
	<thead>
	<tbody>
		<? foreach( $_["groups"] as $group ){ ?>
			<tr>
				<td><? echo $group["name"] ?></td>
				<td>remove</td>
			</tr>
		<? } ?>
	</tbody>
</table>
