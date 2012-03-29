<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}table td{position:static !important;}</style><![endif]-->
<div id="controls">
	<?php echo($_['breadcrumb']); ?>
	<?php if (!isset($_['readonly']) || !$_['readonly']):?>
		<div class="actions">
			<div id='new' class='button'>
				<a>
					<?php echo $l->t('New');?>
				</a>
				<ul class="popup popupTop">
					<li style="background-image:url('<?php echo mimetype_icon('text/plain') ?>')" data-type='file'><p><?php echo $l->t('Text file');?></p></li>
					<li style="background-image:url('<?php echo mimetype_icon('dir') ?>')" data-type='folder'><p><?php echo $l->t('Folder');?></p></li>
	<!-- 				<li style="background-image:url('<?php echo mimetype_icon('dir') ?>')" data-type='web'><p><?php echo $l->t('From the web');?></p></li> -->
				</ul>
			</div>
			<div class="file_upload_wrapper svg">
				<form data-upload-id='1' class="file_upload_form" action="ajax/upload.php" method="post" enctype="multipart/form-data" target="file_upload_target_1">
					<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $_['uploadMaxFilesize'] ?>" id="max_upload">
					<input type="hidden" class="max_human_file_size" value="(max <?php echo $_['uploadMaxHumanFilesize']; ?>)">
					<input type="hidden" name="dir" value="<?php echo $_['dir'] ?>" id="dir">
					<button class="file_upload_filename"><img class='svg action' alt="Upload" src="<?php echo image_path("core", "actions/upload.svg"); ?>" /></button>
					<input class="file_upload_start" type="file" name='files[]'/>
						<a href="#" class="file_upload_button_wrapper" onclick="return false;" title="<?php echo $l->t('Upload'); echo  ' max. '.$_['uploadMaxHumanFilesize'] ?>"></a>
					<iframe name="file_upload_target_1" class='file_upload_target' src=""></iframe>
				</form>
			</div>
		</div>
		<div id="file_action_panel"></div>
	<?php else:?>
		<input type="hidden" name="dir" value="<?php echo $_['dir'] ?>" id="dir">
	<?php endif;?>
</div>
<div id='notification'></div>

<?php if (isset($_['files']) and ! $_['readonly'] and count($_['files'])==0):?>
	<div id="emptyfolder"><?php echo $l->t('Nothing in here. Upload something!')?></div>
<?php endif; ?>

<table>
	<thead>
		<tr>
			<th id='headerName'>
				<?php if(!isset($_['readonly']) || !$_['readonly']) { ?><input type="checkbox" id="select_all" /><?php } ?>
				<span class='name'><?php echo $l->t( 'Name' ); ?></span>
				<span class='selectedActions'>
				<?php if($_['allowZipDownload']) : ?>
					<a href="" title="<?php echo $l->t('Download')?>" class="download"><img class='svg' alt="Download" src="<?php echo image_path("core", "actions/download.svg"); ?>" /></a>
				<?php endif; ?>
				<a href="" title="Share" class="share"><img class='svg' alt="Share" src="<?php echo image_path("core", "actions/share.svg"); ?>" /></a>
				</span>
			</th>
			<th id="headerSize"><?php echo $l->t( 'Size' ); ?></th>
			<th id="headerDate"><span id="modified"><?php echo $l->t( 'Modified' ); ?></span><span class="selectedActions"><a href="" title="Delete" class="delete"><img class="svg" alt="<?php echo $l->t('Delete')?>" src="<?php echo image_path("core", "actions/delete.svg"); ?>" /></a></span></th>
		</tr>
	</thead>
	<tbody id="fileList" data-readonly="<?php echo $_['readonly'];?>">
		<?php echo($_['fileList']); ?>
	</tbody>
</table>
<div id="editor"></div>
<div id="uploadsize-message" title="<?php echo $l->t('Upload too large')?>">
	<p>
		<?php echo $l->t('The files you are trying to upload exceed the maximum size for file uploads on this server.');?>
	</p>
</div>

<!-- config hints for javascript -->
<input type="hidden" name="allowZipDownload" id="allowZipDownload" value="<?php echo $_['allowZipDownload']; ?>" />
