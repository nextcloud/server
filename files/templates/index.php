<div id="controls">
	<?php echo($_['breadcrumb']); ?>
	<div class="actions">
		<form data-upload-id='1' class="file_upload_form" action="ajax/upload.php" method="post" enctype="multipart/form-data" target="file_upload_target_1">
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $_['uploadMaxFilesize'] ?>" id="max_upload">
			<input type="hidden" class="max_human_file_size" value="(max <?php echo $_['uploadMaxHumanFilesize']; ?>)">
			<input type="hidden" name="dir" value="<?php echo $_['dir'] ?>" id="dir">
			<div class="file_upload_wrapper svg">
				<input type="submit" class="file_upload_filename" value="<?php echo $l->t('Upload'); ?>"/>
				<input class="file_upload_start" type="file" name='files[]'/>
				<a href="#" class="file_upload_button_wrapper" onclick="return false;" title="<?php echo  'max. '.$_['uploadMaxHumanFilesize'] ?>"></a>
			</div>
			<iframe name="file_upload_target_1" class='file_upload_target' src=""></iframe>
		</form>
		<form id="file_newfolder_form">
			<input class="svg" type="text" name="file_newfolder_name" id="file_newfolder_name" value="" placeholder="<?php echo $l->t('New Folder')?>" />
		</form>
	</div>
	<div id="file_action_panel">
	</div>
</div>
<div id='notification'></div>

<div id="emptyfolder" <?php if(count($_['files'])) echo 'style="display:none;"';?>><?php echo $l->t('Nothing in here. Upload something!')?></div>

<table>
	<thead>
		<tr>
			<th id='headerName'>
				<input type="checkbox" id="select_all" />
				<span class='name'><?php echo $l->t( 'Name' ); ?></span>
				<span class='selectedActions'>
				<a href="" title="<?php echo $l->t('Download')?>" class="download"><img class='svg' alt="Download" src="<?php echo image_path("core", "actions/download.svg"); ?>" /></a>
				<a href="" title="Share" class="share"><img class='svg' alt="Share" src="<?php echo image_path("core", "actions/share.svg"); ?>" /></a>
				</span>
			</th>
			<th id="headerSize"><?php echo $l->t( 'Size' ); ?></th>
			<th id="headerDate"><span id="modified"><?php echo $l->t( 'Modified' ); ?></span><span class="selectedActions"><a href="" title="Delete" class="delete"><img class="svg" alt="<?php echo $l->t('Delete')?>" src="<?php echo image_path("core", "actions/delete.svg"); ?>" /></a></span></th>
		</tr>
	</thead>
	<tbody id="fileList">
		<?php echo($_['fileList']); ?>
	</tbody>
</table>

<div id="uploadsize-message" title="<?php echo $l->t('Upload too large')?>">
	<p>
		<?php echo $l->t('The files you are trying to upload exceed the maximum size for file uploads on this server.');?>
	</p>
</div>
