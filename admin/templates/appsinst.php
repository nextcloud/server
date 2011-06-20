<?php
/*
 * Template for Installed Apps
 */
?>
<h1><?php echo $l->t( 'Installed Applications' ); ?></h1>

<table>
	<thead>
		<tr>
			<th><?php echo $l->t( 'Name' ); ?></th>
			<th><?php echo $l->t( 'Version' ); ?></th>
			<th><?php echo $l->t( 'Author' ); ?></th>
			<th><?php echo $l->t( 'Status' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_["apps"] as $app): ?>
			<tr x-uid="<?php echo($app['id']); ?>">
				<td class="name" width="200"><?php echo($app['name']); ?></td>
				<td class="version"><?php echo($app['version']); ?></td>
				<td><?php echo($app['author']); ?></td>
				<td class="<?php echo $app['enabled'] ? 'enabled' : 'disabled' ?>"><div x-use="appenableddiv"><?php echo $l->t( $app['enabled'] ? 'enabled' : 'disabled' ); ?></div></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>