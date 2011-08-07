<table cellspacing="0">
	<thead>
		<tr>
			<th></th>
			<th><?php echo $l->t( 'Name' ); ?></th>
			<th><?php echo $l->t( 'Modified' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_["apps"] as $app): ?>
			<tr>
				<td width="1"><?php if($app["preview"] <> "") { echo('<a href="'.OC_Helper::linkTo( "admin", "apps.php" ).'?id='.$app['id'].'"><img class="preview" border="0" src="'.$app["preview"].'" /></a>'); } ?> </a></td>
				<td class="name"><a href="<?php echo(OC_Helper::linkTo( "admin", "apps.php" ).'?id='.$app['id']); ?>" title=""><?php echo $app["name"]; ?></a><br /><?php  echo('<span class="type">'.$app['typename'].'</span>'); ?></td>
				<td class="date"><?php echo $l->l('datetime', $app["changed"]); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

