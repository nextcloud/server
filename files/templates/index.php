<div class="controls">
	<div class="actions">
		<form data-upload-id='1' class="file_upload_form" action="ajax/upload.php" method="post" enctype="multipart/form-data" target="file_upload_target_1">
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $_["uploadMaxFilesize"] ?>" id="max_upload">
			<input type="hidden" class="max_human_file_size" value="(max <?php echo $_["uploadMaxHumanFilesize"]; ?>)">
			<input type="hidden" name="dir" value="<?php echo $_["dir"] ?>" id="dir">
			<div class='file_upload_wrapper'>
				<input class="prettybutton file_upload_filename" value="Upload (max. <?php echo $_["uploadMaxHumanFilesize"];?>)"/>
				<input class="prettybutton file_upload_start" type="file" name='files[]'/>
			</div>&nbsp;
			<iframe name="file_upload_target_1" class='file_upload_target' src=""></iframe>
		</form>
		<form id="file_newfolder_form">
			<input type="text" class="prettybutton" name="file_newfolder_name" id="file_newfolder_name" value="New Folder" />&nbsp;
			<input class="prettybutton" type="submit" id="file_newfolder_submit" name="file_newfolder_submit" value="OK" />
		</form>
		<a href="" title="" class="download"><?php echo $l->t( 'Download' ); ?></a>
		<a href="" title="" class="share"><?php echo $l->t( 'Share' ); ?></a>
		<a href="" title="" class="delete"><?php echo $l->t( 'Delete' ); ?></a>
	</div>
	<div id="file_action_panel">
	</div>
</div>

<span class="nav">
	<?php echo($_['breadcrumb']); ?>
</span>

<table cellspacing="0">
	<thead>
		<tr>
			<th><input type="checkbox" id="select_all" /></th>
			<th><?php echo $l->t( 'Name' ); ?></th>
			<th><?php echo $l->t( 'Size (MB)' ); ?></th>
			<th><?php echo $l->t( 'Modified' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody id="fileList">
		<?php echo($_['fileList']); ?>
	</tbody>
</table>

<div id="file_menu">
	<ul>
	</ul>
</div>
