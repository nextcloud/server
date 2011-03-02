<?php
/*
 * Template for admin pages
 */
?>
<h1>Administration</h1>
<h2>Plugins</h2>

<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Description</th>
			<th>Version</th>
			<th>Author</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<? foreach( $_["plugins"] as $plugin ){ ?>
			<td><? echo $plugin["info"]["id"] ?></td>
			<td><? echo $plugin["info"]["version"] ?></td>
			<td><? echo $plugin["info"]["name"] ?></td>
			<td><? echo $plugin["info"]["author"] ?></td>
			<td>enable</td>
		<? } ?>
	</tbody>
</table>
