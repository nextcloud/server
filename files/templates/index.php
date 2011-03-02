<?php
/*
 * Template for files
 */
?>
<h1>Files</h1>

<div class="controls">
	<p class="actions">
		<a href="" title="" class="upload">Upload</a><a href="" title="" class="new-dir">New folder</a><a href="" title="" class="download">Download</a><a href="" title="" class="share">Share</a><a href="" title="" class="delete">Delete</a>
	</p>
</div>

<p class="nav">
	<a href="<? echo link_to( "files", "index.php?dir=/" ) ?>"><img src="<? echo image_path( "", "actions/go-home.png" ) ?>" alt="Root" /></a>
	<? foreach( $_["breadcrumb"] as $crumb ){ ?>
		<a href="<? echo link_to( "files", "index.php?dir=".$crumb["dir"] ) ?>"><? echo $crumb["name"] ?></a>
	<? } ?>
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
		<? foreach( $_["files"] as $file ){ ?>
			<tr>
				<td class="selection"><input type="checkbox" /></td>
				<td class="filename"><a style="background-image:url(<? if( $file["type"] == "dir" ) echo mimetype_icon( "dir" ); else echo mimetype_icon( $file["mime"] )  ?>)" href="<? if( $file["type"] == "dir" ) echo link_to( "files", "index.php?dir=".$file["directory"]."/".$file["name"] ); else echo link_to( "files", "download.php?file=".$file["directory"]."/".$file["name"] )  ?>" title=""><? echo $file["name"] ?></a></td>
				<td class="filesize"><? if( $file["type"] != "dir" ) echo human_file_size( $file["size"] ) ?></td>
				<td class="date"><? if( $file["type"] != "dir" ) echo $file["date"] ?></td>
				<td class="fileaction"><a href="" title=""><img src="images/drop-arrow.png" alt="+" /></a></td>
			</tr>
		<? } ?>
	</tbody>
</table>

<div id="file_menu">
	<ul>
		<li><a href="" title="">Download</a></li>
		<li><a href="" title="">Share</a></li>
		<li><a href="" title="">Delete</a></li>
	</ul>
</div>
