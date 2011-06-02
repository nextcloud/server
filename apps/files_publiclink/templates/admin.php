<input type='hidden' id='baseUrl' value='<?php echo $_['baseUrl'];?>'/>
<table id='linklist'>
	<thead>
		<tr>
			<td class='path'>Path</td>
			<td class='expire'>Expires</td>
			<td class='link'>Link</td>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_['links'] as $link):?>
			<tr class='link' id='<?php echo $link['token'];?>'>
				<td class='path'><?php echo $link['path'];?></td>
				<td class='expire'><?php echo ($link['expire_time']==0)?'Never':OC_UTIL::formatdate($link['expire_time'],true);?></td>
				<td class='link'><a href='get.php?token=<?php echo $link['token'];?>'><?php echo $_['baseUrl'];?>?token=<?php echo $link['token'];?></a></td>
				<td><button class='delete fancybutton' data-token='<?php echo $link['token'];?>'>Delete</button></td>
			</tr>
		<?php endforeach;?>
		<tr id='newlink_row'>
			<form action='#' id='newlink'>
				<input type='hidden' id='expire_time'/>
				<td class='path'><input placeholder='Path' id='path'/></td>
				<td class='expire'><input placeholder='Expires' id='expire'/></td>
				<td><input type='submit' value='Share'/></td>
			</form>
		</tr>
	</tbody>
</table>