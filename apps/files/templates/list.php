<div class="files-controls">
		<div class="actions creatable hidden">
			<div id="uploadprogresswrapper">
			</div>
		</div>
		<div class="notCreatable notPublic hidden">
			<div class="icon-alert-outline"></div>
			<?php p($l->t('You do not have permission to upload or create files here'))?>
		</div>
	<?php /* Note: the template attributes are here only for the public page. These are normally loaded
			 through ajax instead (updateStorageStatistics).
	*/ ?>
	<input type="hidden" id="permissions" value="">
	<input type="hidden" id="free_space" value="<?php isset($_['freeSpace']) ? p($_['freeSpace']) : '' ?>">
	<?php if (isset($_['dirToken'])):?>
	<input type="hidden" id="publicUploadRequestToken" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
	<input type="hidden" id="dirToken" name="dirToken" value="<?php p($_['dirToken']) ?>" />
	<?php endif;?>
	<input type="hidden" class="max_human_file_size"
		   value="(max <?php isset($_['uploadMaxHumanFilesize']) ? p($_['uploadMaxHumanFilesize']) : ''; ?>)">
</div>
<div class="filelist-header"></div>

<div class="emptyfilelist emptycontent hidden">
	<div class="icon-folder"></div>
	<h2><?php p($l->t('No files in here')); ?></h2>
	<p class="uploadmessage hidden"><?php p($l->t('Upload some content or sync with your devices!')); ?></p>
</div>

<div class="nofilterresults emptycontent hidden">
	<div class="icon-search"></div>
	<h2><?php p($l->t('No entries found in this folder')); ?></h2>
	<p></p>
</div>
<table class="files-filestable list-container <?php p($_['showgridview'] ? 'view-grid' : '') ?>" data-allow-public-upload="<?php p($_['publicUploadEnabled'])?>" data-preview-x="250" data-preview-y="250">
	<thead>
		<tr>
			<th class="hidden column-selection">
				<input type="checkbox" id="select_all_files" class="select-all checkbox"/>
				<label for="select_all_files">
					<span class="hidden-visually"><?php p($l->t('Select all'))?></span>
				</label>
			</th>
			<th class="hidden column-name">
				<div class="column-name-container">
					<a class="name sort columntitle" onclick="event.preventDefault()" href="#" data-sort="name">
                        <span><?php p($l->t('Name')); ?></span>
                        <span class="sort-indicator"></span>

                    </a>
                    <span class="selectedActions">
                        <a href="#" onclick="event.preventDefault()" class="actions-selected">
                            <span class="icon icon-more"></span>
                            <span><?php p($l->t('Actions'))?></span>
                        </a>
					</span>
				</div>
			</th>
			<th class="hidden column-size">
				<a class="size sort columntitle" href="#" onclick="event.preventDefault()" data-sort="size"><span><?php p($l->t('Size')); ?></span><span class="sort-indicator"></span></a>
			</th>
			<th class="hidden column-mtime">
				<a class="columntitle" href="#" onclick="event.preventDefault()" data-sort="mtime"><span><?php p($l->t('Modified')); ?></span><span class="sort-indicator"></span></a>
			</th>
		</tr>
	</thead>
	<tbody class="files-fileList">
	</tbody>
	<tfoot>
	</tfoot>
</table>
<div class="filelist-footer"></div>
<div class="hiddenuploadfield">
	<input type="file" id="file_upload_start" class="hiddenuploadfield" name="files[]" />
</div>
<div id="uploadsize-message" title="<?php p($l->t('Upload too large'))?>">
	<p>
	<?php p($l->t('The files you are trying to upload exceed the maximum size for file uploads on this server.'));?>
	</p>
</div>
