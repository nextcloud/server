	<p id="contacts_details_name" class="contacts_property" data-checksum="<?php echo $_['property']['checksum']; ?>">
		<?php echo $_['property']['value']; ?>
		<span style="display:none;" data-use="edit"><img class="svg action" src="<?php echo image_path('', 'actions/rename.svg'); ?>" /></span>
	</p>
<?php if (!isset($_['details'])): ?>
<script>
$('#leftcontent li.active a').text('<?php echo $_['property']['value']; ?>');
</script>
<?php endif ?>
