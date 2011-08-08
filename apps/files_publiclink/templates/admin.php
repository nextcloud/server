<input type="hidden" id="baseUrl" value="<?php echo $_['baseUrl'];?>"/>
<table id="linklist">
	<tbody>
		<tr id="newlink_row">
			<form action="#" id="newlink">
				<td class="path"><input placeholder="Path" id="path"/></td>
				<td><input type="submit" value="Share" /></td>
			</form>
		</tr>
		<?php foreach($_['links'] as $link):?>
			<tr class="link" id="<?php echo $link['token'];?>">
				<td class="path"><?php echo $link['path'];?></td>
				<td class="link"><a href="get.php?token=<?php echo $link['token'];?>"><?php echo $_['baseUrl'];?>?token=<?php echo $link['token'];?></a></td>
				<td><input type="submit" class="delete" data-token="<?php echo $link['token'];?>" value="<?php echo $l->t( 'Delete' ); ?>" /></td>
			</tr>
		<?php endforeach;?>
	</tbody>
</table>
