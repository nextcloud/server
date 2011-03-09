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
		<?php foreach($_["plugins"] as $plugin): ?>
			<td><?php echo $plugin["info"]["id"] ?></td>
			<td><?php echo $plugin["info"]["version"] ?></td>
			<td><?php echo $plugin["info"]["name"] ?></td>
			<td><?php echo $plugin["info"]["author"] ?></td>
			<td>enable</td>
		<?php endforeach; ?>
	</tbody>
</table>
