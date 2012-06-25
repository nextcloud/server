<div id="selectaddressbook_dialog" title="<?php echo $l->t("Select Address Books"); ?>">
<form>
<table>
	<?php foreach($_['addressbooks'] as $idx => $addressbook) { ?>
	<tr>
		<td>
			<input id="book_<?php echo $addressbook['id']; ?>" name="book" type="radio" value="<?php echo $addressbook['id']; ?>" <?php echo ($idx==0?'checked="checked"':'')?>>
		</td>
		<td>
			<label for="book_<?php echo $addressbook['id']; ?>"><?php echo $addressbook['name']; ?></label>
		</td>
	</tr>
	<?php } ?>
</table>
</form>
</div>

