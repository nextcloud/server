<input type="hidden" id="disableSharing" data-status="<?php p($_['disableSharing']); ?>">

<?php foreach($_['files'] as $file):
	$simple_file_size = OCP\simple_file_size($file['size']);
	// the bigger the file, the darker the shade of grey; megabytes*2
	$simple_size_color = intval(200-$file['size']/(1024*1024)*2);
	if($simple_size_color<0) $simple_size_color = 0;
	$relative_modified_date = OCP\relative_modified_date($file['mtime']);
	// the older the file, the brighter the shade of grey; days*14
	$relative_date_color = round((time()-$file['mtime'])/60/60/24*14);
	if($relative_date_color>200) $relative_date_color = 200;
	$name = str_replace('+', '%20', urlencode($file['name']));
	$name = str_replace('%2F', '/', $name);
	$directory = str_replace('+', '%20', urlencode($file['directory']));
	$directory = str_replace('%2F', '/', $directory); ?>
	<tr data-id="<?php p($file['fileid']); ?>"
		data-file="<?php p($name);?>"
		data-type="<?php ($file['type'] == 'dir')?p('dir'):p('file')?>"
		data-mime="<?php p($file['mimetype'])?>"
		data-size='<?php p($file['size']);?>'
		data-permissions='<?php p($file['permissions']); ?>'>
		<td class="filename svg"
		<?php if($file['type'] == 'dir'): ?>
			style="background-image:url(<?php print_unescaped(OCP\mimetype_icon('dir')); ?>)"
		<?php else: ?>
			style="background-image:url(<?php print_unescaped(OCP\mimetype_icon($file['mimetype'])); ?>)"
		<?php endif; ?>
			>
		<?php if(!isset($_['readonly']) || !$_['readonly']): ?><input type="checkbox" /><?php endif; ?>
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
			title="<?php p(OCP\human_file_size($file['size'])); ?>"
			style="color:rgb(<?php p($simple_size_color.','.$simple_size_color.','.$simple_size_color) ?>)">
				<?php print_unescaped($simple_file_size); ?>
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
