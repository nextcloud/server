<div id="selectaddressbook_dialog" title="<?php echo $l->t("Select Address Books"); ?>">
<script type="text/javascript">
$(document).ready(function() {
	$('input.name,input.desc').on('focus', function(e) {
		$('#book_new').prop('checked', true);
	});
});
</script>
<form>
<table style="width: 100%">
	<?php foreach($_['addressbooks'] as $idx => $addressbook) { ?>
	<tr>
		<td>
			<input id="book_<?php echo $addressbook['id']; ?>" name="book" type="radio" value="<?php echo $addressbook['id']; ?>" <?php echo ($idx==0?'checked="checked"':'')?>>
		</td>
		<td>
			<label for="book_<?php echo $addressbook['id']; ?>"><?php echo $addressbook['displayname']; ?></label>
		</td>
		<td><?php echo $addressbook['description']; ?></td>
	</tr>
	<?php } ?>
	<tr>
		<td>
			<input id="book_new" name="book" type="radio" value="new">
		</td>
		<th>
			<input type="text" class="name" name="displayname" placeholder="<?php echo $l->t("Enter name"); ?>" />
		</th>
		<td><input type="text" class="desc" name="description" placeholder="<?php echo $l->t("Enter description"); ?>" /></td>
	</tr>
</table>
</form>
</div>

