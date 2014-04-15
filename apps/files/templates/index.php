<div id="controls">
		<div class="actions creatable hidden">
			<?php if(!isset($_['dirToken'])):?>
			<div id="new" class="button">
				<a><?php p($l->t('New'));?></a>
				<ul>
					<li class="icon-filetype-text svg"
						data-type="file" data-newname="<?php p($l->t('New text file')) ?>.txt">
						<p><?php p($l->t('Text file'));?></p>
					</li>
					<li class="icon-filetype-folder svg"
						data-type="folder" data-newname="<?php p($l->t('New folder')) ?>">
						<p><?php p($l->t('Folder'));?></p>
					</li>
					<li class="icon-link svg" data-type="web">
						<p><?php p($l->t('From link'));?></p>
					</li>
				</ul>
			</div>
			<?php endif;?>
			<div id="upload" class="button"
				 title="<?php p($l->t('Upload') . ' max. '.$_['uploadMaxHumanFilesize']) ?>">
					<?php if($_['uploadMaxFilesize'] >= 0):?>
					<input type="hidden" id="max_upload" name="MAX_FILE_SIZE" value="<?php p($_['uploadMaxFilesize']) ?>">
					<?php endif;?>
					<input type="hidden" id="upload_limit" value="<?php p($_['uploadLimit']) ?>">
					<input type="hidden" id="free_space" value="<?php p($_['freeSpace']) ?>">
					<?php if(isset($_['dirToken'])):?>
					<input type="hidden" id="publicUploadRequestToken" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
					<input type="hidden" id="dirToken" name="dirToken" value="<?php p($_['dirToken']) ?>" />
					<?php endif;?>
					<input type="hidden" class="max_human_file_size"
						   value="(max <?php p($_['uploadMaxHumanFilesize']); ?>)">
					<input type="hidden" name="dir" value="<?php p($_['dir']) ?>" id="dir">
					<input type="file" id="file_upload_start" name='files[]'
						   data-url="<?php print_unescaped(OCP\Util::linkTo('files', 'ajax/upload.php')); ?>" />
					<a href="#" class="svg icon-upload"></a>
			</div>
			<?php if ($_['trash']): ?>
			<input id="trash" type="button" value="<?php p($l->t('Deleted files'));?>" class="button" <?php $_['trashEmpty'] ? p('disabled') : '' ?> />
			<?php endif; ?>
			<div id="uploadprogresswrapper">
				<div id="uploadprogressbar"></div>
				<input type="button" class="stop" style="display:none"
					value="<?php p($l->t('Cancel upload'));?>"
				/>
			</div>
		</div>
		<div id="file_action_panel"></div>
		<div class="notCreatable notPublic hidden">
			<?php p($l->t('You don’t have permission to upload or create files here'))?>
		</div>
	<input type="hidden" name="permissions" value="<?php p($_['permissions']); ?>" id="permissions">
</div>

<div id="emptycontent" class="hidden"><?php p($l->t('Nothing in here. Upload something!'))?></div>

<input type="hidden" id="disableSharing" data-status="<?php p($_['disableSharing']); ?>" />

<table id="filestable" data-allow-public-upload="<?php p($_['publicUploadEnabled'])?>" data-preview-x="36" data-preview-y="36">
	<thead>
		<tr>
			<th class="hidden" id='headerName'>
				<div id="headerName-container">
					<input type="checkbox" id="select_all" />
					<label for="select_all"></label>
					<span class="name"><?php p($l->t( 'Name' )); ?></span>
					<span id="selectedActionsList" class="selectedActions">
						<?php if($_['allowZipDownload']) : ?>
							<a href="" class="download">
								<img class="svg" alt="Download"
									 src="<?php print_unescaped(OCP\image_path("core", "actions/download.svg")); ?>" />
								<?php p($l->t('Download'))?>
							</a>
						<?php endif; ?>
					</span>
				</div>
			</th>
			<th class="hidden" id="headerSize"><?php p($l->t('Size')); ?></th>
			<th class="hidden" id="headerDate">
				<span id="modified"><?php p($l->t( 'Modified' )); ?></span>
				<?php if ($_['permissions'] & OCP\PERMISSION_DELETE): ?>
					<span class="selectedActions"><a href="" class="delete-selected">
						<?php p($l->t('Delete'))?>
						<img class="svg" alt="<?php p($l->t('Delete'))?>"
							 src="<?php print_unescaped(OCP\image_path("core", "actions/delete.svg")); ?>" />
					</a></span>
				<?php endif; ?>
			</th>
		</tr>
	</thead>
	<tbody id="fileList">
	</tbody>
</table>
<div id="editor"></div><!-- FIXME Do not use this div in your app! It is deprecated and will be removed in the future! -->
<div id="uploadsize-message" title="<?php p($l->t('Upload too large'))?>">
	<p>
	<?php p($l->t('The files you are trying to upload exceed the maximum size for file uploads on this server.'));?>
	</p>
</div>
<div id="scanning-message">
	<h3>
		<?php p($l->t('Files are being scanned, please wait.'));?> <span id='scan-count'></span>
	</h3>
	<p>
		<?php p($l->t('Current scanning'));?> <span id='scan-current'></span>
	</p>
</div>

<!-- config hints for javascript -->
<input type="hidden" name="filesApp" id="filesApp" value="1" />
<input type="hidden" name="allowZipDownload" id="allowZipDownload" value="<?php p($_['allowZipDownload']); ?>" />
<input type="hidden" name="usedSpacePercent" id="usedSpacePercent" value="<?php p($_['usedSpacePercent']); ?>" />
<?php if (!$_['isPublic']) :?>
<input type="hidden" name="encryptedFiles" id="encryptedFiles" value="<?php $_['encryptedFiles'] ? p('1') : p('0'); ?>" />
<input type="hidden" name="encryptedInitStatus" id="encryptionInitStatus" value="<?php p($_['encryptionInitStatus']) ?>" />
<input type="hidden" name="mailNotificationEnabled" id="mailNotificationEnabled" value="<?php p($_['mailNotificationEnabled']) ?>" />
<input type="hidden" name="allowShareWithLink" id="allowShareWithLink" value="<?php p($_['allowShareWithLink']) ?>" />
<?php endif;
