<input type="hidden" id="disableSharing" data-status="<?php p($_['disableSharing']); ?>">
<?php foreach($_['files'] as $file):
	//strlen('files/') => 6
	$relativePath = substr($file['path'], 6);
	// the bigger the file, the darker the shade of grey; megabytes*2
	$simple_size_color = intval(160-$file['size']/(1024*1024)*2);
	if($simple_size_color<0) $simple_size_color = 0;
	$relative_modified_date = OCP\relative_modified_date($file['mtime']);
	// the older the file, the brighter the shade of grey; days*14
	$relative_date_color = round((time()-$file['mtime'])/60/60/24*14);
	if($relative_date_color>160) $relative_date_color = 160;
	$name = \OCP\Util::encodePath($file['name']);
	$directory = \OCP\Util::encodePath($file['directory']); ?>
	<tr data-id="<?php p($file['fileid']); ?>"
		data-file="<?php p($name);?>"
		data-type="<?php ($file['type'] == 'dir')?p('dir'):p('file')?>"
		data-mime="<?php p($file['mimetype'])?>"
		data-size="<?php p($file['size']);?>"
		data-permissions="<?php p($file['permissions']); ?>">
		<?php if($file['isPreviewAvailable']): ?>
		<td class="filename svg preview-icon"
		<?php else: ?>
		<td class="filename svg"
		<?php endif; ?>
		<?php if($file['type'] == 'dir'): ?>
			style="background-image:url(<?php print_unescaped(OCP\mimetype_icon('dir')); ?>)"
		<?php else: ?>
			<?php if($_['isPublic']): ?>
				<?php
				$relativePath = substr($relativePath, strlen($_['sharingroot']));
				?>
				<?php if($file['isPreviewAvailable']): ?>
				style="background-image:url(<?php print_unescaped(OCP\publicPreview_icon($relativePath, $_['sharingtoken'])); ?>)"
				<?php else: ?>
				style="background-image:url(<?php print_unescaped(OCP\mimetype_icon($file['mimetype'])); ?>)"
				<?php endif; ?>
			<?php else: ?>
				<?php if($file['isPreviewAvailable']): ?>
				style="background-image:url(<?php print_unescaped(OCP\preview_icon($relativePath)); ?>)"
				<?php else: ?>
				style="background-image:url(<?php print_unescaped(OCP\mimetype_icon($file['mimetype'])); ?>)"
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
			>
		<?php if(!isset($_['readonly']) || !$_['readonly']): ?>
			<input id="select-<?php p($file['fileid']); ?>" type="checkbox" />
			<label for="select-<?php p($file['fileid']); ?>"></label>
		<?php endif; ?>
		<?php if($file['type'] == 'dir'): ?>
			<a class="name" href="<?php p(rtrim($_['baseURL'],'/').'/'.trim($directory,'/').'/'.$name); ?>" title="">
		<?php else: ?>
			<a class="name" href="<?php p(rtrim($_['downloadURL'],'/').'/'.trim($directory,'/').'/'.$name); ?>" title="">
		<?php endif; ?>
			<span class="nametext">
				<?php if($file['type'] == 'dir'):?>
					<?php print_unescaped(htmlspecialchars($file['name']));?>
				<?php else:?>
					<?php print_unescaped(htmlspecialchars($file['basename']));?><span class='extension'><?php p($file['extension']);?></span>
				<?php endif;?>
			</span>
			<?php if($file['type'] == 'dir'):?>
				<span class="uploadtext" currentUploads="0">
				</span>
			<?php endif;?>
			</a>
		</td>
		<td class="filesize"
			style="color:rgb(<?php p($simple_size_color.','.$simple_size_color.','.$simple_size_color) ?>)">
				<?php print_unescaped(OCP\human_file_size($file['size'])); ?>
		</td>
		<td class="date">
			<span class="modified"
				  title="<?php p($file['date']); ?>"
				  style="color:rgb(<?php p($relative_date_color.','
												.$relative_date_color.','
												.$relative_date_color) ?>)">
				<?php p($relative_modified_date); ?>
			</span>
		</td>
	</tr>
<?php endforeach;
