<div class="controls">
	<div class="actions">
		<form id="file_upload_form" action="ajax/upload.php"
method="post" enctype="multipart/form-data" target="file_upload_target"><input
type="hidden" name="MAX_FILE_SIZE" value="<?php echo $_["uploadMaxFilesize"] ?>" id="max_upload"><input
type="hidden" class="max_human_file_size" value="(max <?php echo $_["uploadMaxHumanFilesize"]; ?>)"><input type="hidden" name="dir" value="<?php echo $_["dir"] ?>" id="dir"><input class="prettybutton" type="submit" id="file_upload_start" value="Upload (max <?php echo $_["uploadMaxHumanFilesize"];?>)" />&nbsp;<input class="prettybutton" type="button" id="file_upload_cancel" value="X" /><input type="file" name="file" id="fileSelector"><iframe id="file_upload_target" name="file_upload_target" src=""></iframe></form><a href="" title="" class="new-dir">New folder</a><a href="" title="" class="download">Download</a><a href="" title="" class="share">Share</a><a href="" title="" class="delete">Delete</a>
	</div>
	<div id="file_action_panel">
		<form id="file_newfolder_form"><input type="text" name="file_new_dir_name" id="file_new_dir_name" />&nbsp;<input class="prettybutton" type="button" id="file_new_dir_submit" name="file_new_dir_submit" value="OK" /></form>
	</div>
</div>

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

<div id="file_menu">
	<ul>
		<li><a href="" title="" id="download_single_file">Download</a></li>
		<li><a href="" title="">Share</a></li>
		<li><a href="" title="" id="delete_single_file">Delete</a></li>
	</ul>
</div>
