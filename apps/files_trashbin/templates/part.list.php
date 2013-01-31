<input type="hidden" id="disableSharing" data-status="<?php echo $_['disableSharing']; ?>">
<?php foreach($_['files'] as $file):
	$simple_file_size = OCP\simple_file_size($file['size']);
	// the bigger the file, the darker the shade of grey; megabytes*2
	$simple_size_color = intval(200-$file['size']/(1024*1024)*2);
	if($simple_size_color<0) $simple_size_color = 0;
	$relative_deleted_date = OCP\relative_modified_date($file['timestamp']);
	// the older the file, the brighter the shade of grey; days*14
	$relative_date_color = round((time()-$file['mtime'])/60/60/24*14);
	if($relative_date_color>200) $relative_date_color = 200;
	$name = str_replace('+', '%20', urlencode($file['name']));
	$name = str_replace('%2F', '/', $name);
	$directory = str_replace('+', '%20', urlencode($file['directory']));
	$directory = str_replace('%2F', '/', $directory); ?>
	<tr data-filename="<?php echo $file['name'];?>"
		data-type="<?php echo ($file['type'] == 'dir')?'dir':'file'?>"
		data-mime="<?php echo $file['mimetype']?>"
		data-permissions='<?php echo $file['permissions']; ?>'
		<?php if ( $_['dirlisting'] ): ?>
		id="<?php echo $file['directory'].'/'.$file['name'];?>"
		data-file="<?php echo $file['directory'].'/'.$file['name'];?>"
		data-timestamp=''
		data-dirlisting=1
		<?php  else: ?>
		id="<?php echo $file['name'].'.d'.$file['timestamp'];?>"
		data-file="<?php echo $file['name'].'.d'.$file['timestamp'];?>"
		data-timestamp='<?php echo $file['timestamp'];?>'
		data-dirlisting=0
		<?php endif; ?>>
		<td class="filename svg"
		<?php if($file['type'] == 'dir'): ?>
			style="background-image:url(<?php echo OCP\mimetype_icon('dir'); ?>)"
		<?php else: ?>
			style="background-image:url(<?php echo OCP\mimetype_icon($file['mimetype']); ?>)"
		<?php endif; ?>
			>
		<?php if(!isset($_['readonly']) || !$_['readonly']): ?><input type="checkbox" /><?php endif; ?>
		<?php if($file['type'] == 'dir'): ?>
			<?php if( $_['dirlisting'] ): ?>
				<a class="name" href="<?php echo $_['baseURL'].'/'.$name; ?>" title="">
			<?php else: ?>
				<a class="name" href="<?php echo $_['baseURL'].'/'.$name.'.d'.$file['timestamp']; ?>" title="">
			<?php endif; ?>
		<?php else: ?>
			<?php if( $_['dirlisting'] ): ?>
				<a class="name" href="<?php echo $_['downloadURL'].'/'.$name; ?>" title="">
			<?php else: ?>
				<a class="name" href="<?php echo $_['downloadURL'].'/'.$name.'.d'.$file['timestamp'];?>" title="">
			<?php endif; ?>
		<?php endif; ?>
			<span class="nametext">
				<?php if($file['type'] == 'dir'):?>
					<?php echo htmlspecialchars($file['name']);?>
				<?php else:?>
					<?php echo htmlspecialchars($file['basename']);?><span
						class='extension'><?php echo $file['extension'];?></span>
				<?php endif;?>
			</span>
			<?php if($file['type'] == 'dir'):?>
				<span class="uploadtext" currentUploads="0">
				</span>
			<?php endif;?>
			</a>
		</td>
		<td class="date">
			<span class="modified"
				  title="<?php echo $file['date']; ?>"
				  style="color:rgb(<?php echo $relative_date_color.','
												.$relative_date_color.','
												.$relative_date_color ?>)">
				<?php echo $relative_deleted_date; ?>
			</span>
		</td>
	</tr>
<?php endforeach;
 