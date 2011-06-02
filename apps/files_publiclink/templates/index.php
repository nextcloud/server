<p class="nav">
	<?php echo($_['breadcrumb']); ?>
</p>
<table cellspacing="0">
	<thead>
		<tr>
			<th><input type="checkbox" id="select_all" /></th>
			<th>Name</th>
			<th>Size</th>
			<th>Modified</th>
			<th></th>
		</tr>
	</thead>
	<tbody id="fileList">
		<?php echo($_['fileList']); ?>
	</tbody>
</table>