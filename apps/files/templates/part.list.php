<?php $totalfiles = 0;
$totaldirs = 0;
$totalsize = 0; ?>
<?php foreach($_['files'] as $file):
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
		data-etag="<?php p($file['etag']);?>"
		data-permissions="<?php p($file['permissions']); ?>">
		<?php if($file['isPreviewAvailable']): ?>
		<td class="filename svg preview-icon"
		<?php else: ?>
		<td class="filename svg"
		<?php endif; ?>
		    style="background-image:url(<?php print_unescaped($file['icon']); ?>)"
			>
		<?php if(!isset($_['readonly']) || !$_['readonly']): ?>
			<input id="select-<?php p($file['fileid']); ?>" type="checkbox" />
			<label for="select-<?php p($file['fileid']); ?>"></label>
		<?php endif; ?>
		<?php if($file['type'] == 'dir'): ?>
			<a class="name" href="<?php p(rtrim($_['baseURL'],'/').'/'.trim($directory,'/').'/'.$name); ?>" title="">
				<span class="nametext">
					<?php print_unescaped(htmlspecialchars($file['name']));?>
				</span>
		<?php else: ?>
			<a class="name" href="<?php p(rtrim($_['downloadURL'],'/').'/'.trim($directory,'/').'/'.$name); ?>">
				<label class="filetext" title="" for="select-<?php p($file['fileid']); ?>"></label>
				<span class="nametext"><?php print_unescaped(htmlspecialchars($file['basename']));?><span class='extension'><?php p($file['extension']);?></span></span>
			</a>
		<?php endif; ?>
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
