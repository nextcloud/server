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
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_["apps"] as $app): ?>
			<tr>
				<td class="filename"><?php if($app["preview"] <> "") { echo('<a href=""><img border="0" src="'.$app["preview"].'" /></a>'); } ?> </a></td>
				<td class="filename"><a href="" title=""><?php echo $app["name"]; ?></a></td>
				<td class="date"><?php echo date($app["changed"]); ?></td>
				<td class="fileaction"><a href="" title=""><img src="images/drop-arrow.png" alt="+" /></a></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

