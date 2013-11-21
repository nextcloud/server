<div id="controls">
	<?php print_unescaped($_['breadcrumb']); ?>
		<div class="actions creatable <?php if (!$_['isCreatable']):?>hidden<?php endif; ?>">
			<div id="new" class="button">
				<a><?php p($l->t('New'));?></a>
				<ul>
					<li style="background-image:url('<?php p(OCP\mimetype_icon('text/plain')) ?>')"
						data-type='file'><p><?php p($l->t('Text file'));?></p></li>
					<li style="background-image:url('<?php p(OCP\mimetype_icon('dir')) ?>')"
						data-type='folder'><p><?php p($l->t('Folder'));?></p></li>
					<li style="background-image:url('<?php p(OCP\image_path('core', 'places/link.svg')) ?>')"
						data-type='web'><p><?php p($l->t('From link'));?></p></li>
				</ul>
			</div>
			<div id="upload" class="button"
				 title="<?php p($l->t('Upload') . ' max. '.$_['uploadMaxHumanFilesize']) ?>">
					<?php if($_['uploadMaxFilesize'] >= 0):?>
					<input type="hidden" name="MAX_FILE_SIZE" id="max_upload"
						   value="<?php p($_['uploadMaxFilesize']) ?>">
					<?php endif;?>
					<input type="hidden" class="max_human_file_size"
						   value="(max <?php p($_['uploadMaxHumanFilesize']); ?>)">
					<input type="hidden" name="dir" value="<?php p($_['dir']) ?>" id="dir">
					<input type="file" id="file_upload_start" name='files[]'
						   data-url="<?php print_unescaped(OCP\Util::linkTo('files', 'ajax/upload.php')); ?>" />
					<a href="#" class="svg"></a>
			</div>
			<?php if ($_['trash']): ?>
			<input id="trash" type="button" value="<?php p($l->t('Deleted files'));?>" class="button" <?php $_['trashEmpty'] ? p('disabled') : '' ?>></input>
			<?php endif; ?>
			<div id="uploadprogresswrapper">
				<div id="uploadprogressbar"></div>
				<input type="button" class="stop" style="display:none"
					value="<?php p($l->t('Cancel upload'));?>"
				/>
			</div>
		</div>
		<div id="file_action_panel"></div>
		<div class="notCreatable notPublic <?php if ($_['isCreatable'] or $_['isPublic'] ):?>hidden<?php endif; ?>">
			<?php p($l->t('You don’t have permission to upload or create files here'))?>
		</div>
	<input type="hidden" name="permissions" value="<?php p($_['permissions']); ?>" id="permissions">
</div>

<div id="emptycontent" <?php if (!$_['emptyContent']):?>class="hidden"<?php endif; ?>><?php p($l->t('Nothing in here. Upload something!'))?></div>

<input type="hidden" id="disableSharing" data-status="<?php p($_['disableSharing']); ?>"></input>

<table id="filestable" data-allow-public-upload="<?php p($_['publicUploadEnabled'])?>" data-preview-x="36" data-preview-y="36">
	<thead>
		<tr>
			<th <?php if (!$_['fileHeader']):?>class="hidden"<?php endif; ?> id='headerName'>
				<div id="headerName-container">
					<input type="checkbox" id="select_all" />
					<label for="select_all"></label>
					<span class="name"><?php p($l->t( 'Name' )); ?></span>
					<span class="selectedActions">
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
			<th <?php if (!$_['fileHeader']):?>class="hidden"<?php endif; ?> id="headerSize"><?php p($l->t('Size')); ?></th>
			<th <?php if (!$_['fileHeader']):?>class="hidden"<?php endif; ?> id="headerDate">
				<span id="modified"><?php p($l->t( 'Modified' )); ?></span>
				<?php if ($_['permissions'] & OCP\PERMISSION_DELETE): ?>
<!--					NOTE: Temporary fix to allow unsharing of files in root of Shared folder -->
					<?php if ($_['dir'] == '/Shared'): ?>
						<span class="selectedActions"><a href="" class="delete-selected">
							<?php p($l->t('Unshare'))?>
							<img class="svg" alt="<?php p($l->t('Unshare'))?>"
								 src="<?php print_unescaped(OCP\image_path("core", "actions/delete.svg")); ?>" />
						</a></span>
					<?php else: ?>
						<span class="selectedActions"><a href="" class="delete-selected">
							<?php p($l->t('Delete'))?>
							<img class="svg" alt="<?php p($l->t('Delete'))?>"
								 src="<?php print_unescaped(OCP\image_path("core", "actions/delete.svg")); ?>" />
						</a></span>
					<?php endif; ?>
				<?php endif; ?>
			</th>
		</tr>
	</thead>
	<tbody id="fileList">
		<?php print_unescaped($_['fileList']); ?>
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
<input type="hidden" name="ajaxLoad" id="ajaxLoad" value="<?php p($_['ajaxLoad']); ?>" />
<input type="hidden" name="allowZipDownload" id="allowZipDownload" value="<?php p($_['allowZipDownload']); ?>" />
<input type="hidden" name="usedSpacePercent" id="usedSpacePercent" value="<?php p($_['usedSpacePercent']); ?>" />
<?php if (!$_['isPublic']) :?>
<input type="hidden" name="encryptedFiles" id="encryptedFiles" value="<?php $_['encryptedFiles'] ? p('1') : p('0'); ?>" />
<input type="hidden" name="encryptedInitStatus" id="encryptionInitStatus" value="<?php p($_['encryptionInitStatus']) ?>" />
<input type="hidden" name="mailNotificationEnabled" id="mailNotificationEnabled" value="<?php p($_['mailNotificationEnabled']) ?>" />
<input type="hidden" name="allowShareWithLink" id="allowShareWithLink" value="<?php p($_['allowShareWithLink']) ?>" />
<?php endif;
