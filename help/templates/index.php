<table cellspacing="0">
	<tbody>
		<?php foreach($_["kbe"] as $kb): ?>
			<tr>
				<td width="1"><?php if($kb["preview1"] <> "") { echo('<a href="'.OC_HELPER::linkTo( "help", "index.php" ).'?id='.$kb['id'].'"><img class="preview" border="0" src="'.$kb["preview1"].'" /></a>'); } ?> </a></td>
				<td class="name"><a href="<?php echo(OC_HELPER::linkTo( "help", "index.php" ).'?id='.$kb['id']); ?>" title=""><?php echo $kb["name"]; ?></a><br /><?php  echo('<span class="type">'.$kb['description'].'</span>'); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
