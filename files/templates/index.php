<div class="controls">
	<span class="nav">
		<?php echo($_['breadcrumb']); ?>
	</span>
	<div class="actions">
		<form data-upload-id='1' class="file_upload_form" action="ajax/upload.php" method="post" enctype="multipart/form-data" target="file_upload_target_1">
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $_['uploadMaxFilesize'] ?>" id="max_upload">
			<input type="hidden" class="max_human_file_size" value="(max <?php echo $_['uploadMaxHumanFilesize']; ?>)">
			<input type="hidden" name="dir" value="<?php echo $_['dir'] ?>" id="dir">
			<div class="file_upload_wrapper">
				<input type="submit" class="file_upload_filename" value="Upload (max. <?php echo $_['uploadMaxHumanFilesize'];?>)"/>
				<input class="file_upload_start" type="file" name='files[]'/>
			</div>
			<iframe name="file_upload_target_1" class='file_upload_target' src=""></iframe>
		</form>
		<form id="file_newfolder_form">
			<input type="text" name="file_newfolder_name" id="file_newfolder_name" value="" placeholder="New Folder" />
		</form>
	</div>
	<div id="file_action_panel">
	</div>
</div>
<div id='notification'></div>

<table cellspacing="0">
	<thead>
		<tr>
			<th id='headerName'>
				<input type="checkbox" id="select_all" />
				<span class='name'><?php echo $l->t( 'Name' ); ?></span>
				<span class='selectedActions'>
					<a href="" title="Download" class="download"><img class='svg' alt="Download" src="../core/img/actions/download.svg" /></a>
					<!--<a href="" title="" class="share">Share</a>-->
				</span>
			</th>
			<th id='headerSize'><?php echo $l->t( 'Size MB' ); ?></th>
			<th id='headerDate'><span id="modified"><?php echo $l->t( 'Modified' ); ?></span><span class='selectedActions'><a href="" title="Delete" class="delete"><img class='svg' alt="Delete" src="../core/img/actions/delete.svg" /></a></span></th>
		</tr>
	</thead>
	<tbody id="fileList">
		<?php echo($_['fileList']); ?>
	</tbody>
</table>

<div id="uploadsize-message" title="Upload too large">
	<p>
		<?php echo $l->t( 'The files you are trying to upload exceed the maximum size for file uploads on this server.' ); ?>
	</p>
</div>

<div id="delete-confirm" title="">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
</div>

<span id="file_menu"/>
