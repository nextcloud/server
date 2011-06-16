<?php if ($_['shared_items'] == null) {echo "You are not sharing any of your files";} else {?>
<table id='itemlist'>
	<thead>
		<tr>
			<th>Item</th>
			<th>Shared With</th>
			<th>Permissions</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($_['shared_items'] as $item):?>
			<tr class='link' id='<?php echo $item['id'];?>'>
				<td class='item'><?php echo $item['item'];?></td>
				<td class='uid_shared_with'><?php echo $item['uid_shared_with'];?></td>
				<td class='permissions'><?php echo $item['permissions'];?></td>
				<td><button class='delete fancybutton' data-token='<?php echo $link['token'];?>'>Delete</button></td>
			</tr>
		<?php endforeach;?>
		<tr id='newlink_row'>
			<form action='#' id='newlink'>
				<input type='hidden' id='expire_time'/>
				<td class='path'><input placeholder='Item' id='path'/></td>
				<td class='expire'><input placeholder='Share With' id='expire'/></td>
				<td class='permissions'><input placeholder='Permissions' id='expire'/></td>
				<td><input type='submit' value='Share'/></td>
			</form>
		</tr>
	</tbody>
</table>
<?php } ?>