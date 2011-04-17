<div class="controls">
	<p class="actions">
		<a href="" title="" class="upload" id="file_upload_button">Upload</a><a
href="" title="" class="new-dir">New folder</a><a href="" title=""
class="download">Download</a><a href="" title="" class="share">Share</a><a
href="" title="" class="delete">Delete</a>
	</p>
	<div id="file_upload_form">
		<form action="ajax/upload.php"
method="post" enctype="multipart/form-data" target="file_upload_target"><input
type="hidden" name="MAX_FILE_SIZE" value="<?php echo $_["uploadMaxFilesize"] ?>" id="max_upload"><input
type="hidden" name="dir" value="<?php echo $_["dir"] ?>" id="dir"><input
type="file" name="file" id="fileSelector"><input type="submit"
id="file_upload_start" value="Upload" /><iframe id="file_upload_target"
name="file_upload_target" src=""></iframe></form>
	</div>
	<div id="file_action_panel">
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
		<li><a href="" title="">Download</a></li>
		<li><a href="" title="">Share</a></li>
		<li><a href="" title="">Delete</a></li>
	</ul>
</div>
