<?php if ($_['shared_items'] == null) {echo "You are not sharing any of your files";} else {?>
<table id='itemlist'>
	<thead>
		<tr>
			<td class='item'>Item</td>
			<td class='uid_shared_with'>Shared With</td>
			<td class='link'>Link</td>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_['shared_items'] as $item):?>
			<tr class='link' id='<?php echo $item['id'];?>'>
				<td class='item'><?php echo $link['item'];?></td>
				<td class='uid_shared_with'><?php echo $item['uid_shared_with'];?></td>
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
<?php } ?>