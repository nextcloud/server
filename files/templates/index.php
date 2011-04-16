<?php
/*
 * Template for files
 */
?>
<h1>Files</h1>

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
type="hidden" name="MAX_FILE_SIZE" value="2097152" id="max_upload"><input
type="hidden" name="dir" value="<?php echo $_["dir"] ?>" id="dir"><input
type="file" name="file" id="fileSelector"><input type="submit"
id="file_upload_start" value="Upload" /><iframe id="file_upload_target"
name="file_upload_target" src=""></iframe></form>
	</div>
</div>

<p class="nav">
	<a href="<?php echo link_to("files", "index.php?dir=/"); ?>"><img src="<?php echo image_path("", "actions/go-home.png"); ?>" alt="Root" /></a>
	<?php foreach($_["breadcrumb"] as $crumb): ?>
		<a href="<?php echo link_to("files", "index.php?dir=".$crumb["dir"]); ?>"><?php echo $crumb["name"]; ?></a>
	<?php endforeach; ?>
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
	<tbody>
		<?php foreach($_["files"] as $file): ?>
			<tr>
				<td class="selection"><input type="checkbox" /></td>
				<td class="filename"><a style="background-image:url(<?php if($file["type"] == "dir") echo mimetype_icon("dir"); else echo mimetype_icon($file["mime"]); ?>)" href="<?php if($file["type"] == "dir") echo link_to("files", "index.php?dir=".$file["directory"]."/".$file["name"]); else echo link_to("files", "download.php?file=".$file["directory"]."/".$file["name"]); ?>" title=""><?php echo $file["name"]; ?></a></td>
				<td class="filesize"><?php echo human_file_size($file["size"]); ?></td>
				<td class="date"><?php if($file["type"] != "dir") echo $file["date"]; ?></td>
				<td class="fileaction"><a href="" title=""><img src="images/drop-arrow.png" alt="+" /></a></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<div id="file_menu">
	<ul>
		<li><a href="" title="">Download</a></li>
		<li><a href="" title="">Share</a></li>
		<li><a href="" title="">Delete</a></li>
	</ul>
</div>
