<?php
/*
 * Template for Apps
 */
?>
<h1>Apps Repository</h1>


<table cellspacing="0">
	<thead>
		<tr>
			<th></th>
			<th>Name</th>
			<th>Modified</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_["apps"] as $app): ?>
			<tr>
				<td width="1"><?php if($app["preview"] <> "") { echo('<a href="'.OC_HELPER::linkTo( "admin", "apps.php" ).'?id='.$app['id'].'"><img class="preview" border="0" src="'.$app["preview"].'" /></a>'); } ?> </a></td>
				<td class="name"><a href="<?php echo(OC_HELPER::linkTo( "admin", "apps.php" ).'?id='.$app['id']); ?>" title=""><?php echo $app["name"]; ?></a><br /><?php  echo('<span class="type">'.$app['typename'].'</span>'); ?></td>
				<td class="date"><?php echo OC_UTIL::formatdate($app["changed"]); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

