		<?php foreach($_['files'] as $file):
			$write = ($file['writable']) ? 'true' : 'false';
			$simple_file_size = OCP\simple_file_size($file['size']);
			$simple_size_color = intval(200-$file['size']/(1024*1024)*2); // the bigger the file, the darker the shade of grey; megabytes*2
			if($simple_size_color<0) $simple_size_color = 0;
			$relative_modified_date = OCP\relative_modified_date($file['mtime']);
			$relative_date_color = round((time()-$file['mtime'])/60/60/24*14); // the older the file, the brighter the shade of grey; days*14
			if($relative_date_color>200) $relative_date_color = 200;
			$name = str_replace('+','%20',urlencode($file['name']));
			$name = str_replace('%2F','/', $name);
			$directory = str_replace('+','%20',urlencode($file['directory']));
			$directory = str_replace('%2F','/', $directory); ?>
			<tr data-file="<?php echo $name;?>" data-type="<?php echo ($file['type'] == 'dir')?'dir':'file'?>" data-mime="<?php echo $file['mimetype']?>" data-size='<?php echo $file['size'];?>' data-write='<?php echo $write;?>'>
				<td class="filename svg" style="background-image:url(<?php if($file['type'] == 'dir') echo OCP\mimetype_icon('dir'); else echo OCP\mimetype_icon($file['mimetype']); ?>)">
					<?php if(!isset($_['readonly']) || !$_['readonly']) { ?><input type="checkbox" /><?php } ?>
					<a class="name" href="<?php if($file['type'] == 'dir') echo $_['baseURL'].$directory.'/'.$name; else echo $_['downloadURL'].$directory.'/'.$name; ?>" title="">
					<span class="nametext">
						<?php if($file['type'] == 'dir'):?>
							<?php echo htmlspecialchars($file['name']);?>
						<?php else:?>
							<?php echo htmlspecialchars($file['basename']);?><span class='extension'><?php echo $file['extension'];?></span>
						<?php endif;?>
					</span>
					<?php if($file['type'] == 'dir'):?>
						<span class="uploadtext" currentUploads="0">
						</span>
					<?php endif;?>
					</a>
				</td>
				<td class="filesize" title="<?php echo OCP\human_file_size($file['size']); ?>" style="color:rgb(<?php echo $simple_size_color.','.$simple_size_color.','.$simple_size_color ?>)"><?php echo $simple_file_size; ?></td>
				<td class="date"><span class="modified" title="<?php echo $file['date']; ?>" style="color:rgb(<?php echo $relative_date_color.','.$relative_date_color.','.$relative_date_color ?>)"><?php echo $relative_modified_date; ?></span></td>
			</tr>
		<?php endforeach; ?>
