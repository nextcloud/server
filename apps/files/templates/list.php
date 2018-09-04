<div id="controls">
		<div class="actions creatable hidden">
			<div id="uploadprogresswrapper">
				<div id="uploadprogressbar">
					<em class="label outer" style="display:none"><span class="desktop"><?php p($l->t('Uploading …'));?></span><span class="mobile"><?php p($l->t('…'));?></span></em>
				</div>
				<button class="stop icon-close" style="display:none">
					<span class="hidden-visually"><?php p($l->t('Cancel upload')) ?></span>
				</button>
			</div>
		</div>
		<div id="file_action_panel"></div>
		<div class="notCreatable notPublic hidden">
			<?php p($l->t('You don’t have permission to upload or create files here'))?>
		</div>
	<?php /* Note: the template attributes are here only for the public page. These are normally loaded
			 through ajax instead (updateStorageStatistics).
	*/ ?>
	<input type="hidden" name="permissions" value="" id="permissions">
	<input type="hidden" id="free_space" value="<?php isset($_['freeSpace']) ? p($_['freeSpace']) : '' ?>">
	<?php if(isset($_['dirToken'])):?>
	<input type="hidden" id="publicUploadRequestToken" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
	<input type="hidden" id="dirToken" name="dirToken" value="<?php p($_['dirToken']) ?>" />
	<?php endif;?>
	<input type="hidden" class="max_human_file_size"
		   value="(max <?php isset($_['uploadMaxHumanFilesize']) ? p($_['uploadMaxHumanFilesize']) : ''; ?>)">
	<button id="view-button" class="button view-switcher icon-toggle-pictures"><span class="hidden-visually"><?php p($l->t('Toggle grid view'))?></button>
</div>

<div id="emptycontent" class="hidden">
	<div class="icon-folder"></div>
	<h2><?php p($l->t('No files in here')); ?></h2>
	<p class="uploadmessage hidden"><?php p($l->t('Upload some content or sync with your devices!')); ?></p>
</div>

<div class="nofilterresults emptycontent hidden">
	<div class="icon-search"></div>
	<h2><?php p($l->t('No entries found in this folder')); ?></h2>
	<p></p>
</div>

<div id="filestable" class="view-grid list-container" data-allow-public-upload="<?php p($_['publicUploadEnabled'])?>" data-preview-x="32" data-preview-y="32">
	<div class="list-header">
		<div>
			<div id="headerSelection" class="hidden list-column column-selection">
				<input type="checkbox" id="select_all_files" class="select-all checkbox"/>
				<label for="select_all_files">
					<span class="hidden-visually"><?php p($l->t('Select all'))?></span>
				</label>
			</div>
			<div id='headerName' class="hidden list-column column-name">
				<div id="headerName-container">
					<a class="name sort columntitle" data-sort="name">
                        <span><?php p($l->t( 'Name' )); ?></span>
                        <span class="sort-indicator"></span>

                    </a>
                    <span id="selectedActionsList" class="selectedActions">
                        <a href="" class="actions-selected">
                            <span class="icon icon-more"></span>
                            <span><?php p($l->t('Actions'))?></span>
                        </a>
					</span>
				</div>
			</div>
			<div id="headerSize" class="hidden list-column column-size">
				<a class="size sort columntitle" data-sort="size"><span><?php p($l->t('Size')); ?></span><span class="sort-indicator"></span></a>
			</div>
			<div id="headerDate" class="hidden list-column column-mtime">
				<a id="modified" class="columntitle" data-sort="mtime"><span><?php p($l->t( 'Modified' )); ?></span><span class="sort-indicator"></span></a>
			</div>
		</div>
	</div>
	<div id="fileList" class="list-body">
	</div>
	<div class="list-footer">
	</div>
</div>
<input type="hidden" name="dir" id="dir" value="" />
<div class="hiddenuploadfield">
	<input type="file" id="file_upload_start" class="hiddenuploadfield" name="files[]" />
</div>
<div id="editor"></div><!-- FIXME Do not use this div in your app! It is deprecated and will be removed in the future! -->
<div id="uploadsize-message" title="<?php p($l->t('Upload too large'))?>">
	<p>
	<?php p($l->t('The files you are trying to upload exceed the maximum size for file uploads on this server.'));?>
	</p>
</div>
