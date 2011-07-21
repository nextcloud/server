		<?php foreach($_['files'] as $file):
			$simple_file_size = simple_file_size($file['size']);
			$simple_size_color = 200-intval(pow(($file['size']/(1024*1024)),2)); ?>
			<tr data-file="<?php echo $file['name'];?>" data-type="<?php echo ($file['type'] == 'dir')?'dir':'file'?>" data-mime="<?php echo $file['mime']?>" data-size='<?php echo $file['size'];?>'>
				<td class="filename">
					<input type="checkbox" />
					<a style="background-image:url(<?php if($file['type'] == 'dir') echo mimetype_icon('dir'); else echo mimetype_icon($file['mime']); ?>)" href="<?php if($file['type'] == 'dir') echo link_to('files', 'index.php?dir='.$file['directory'].'/'.$file['name']); else echo link_to('files', 'download.php?file='.$file['directory'].'/'.$file['name']); ?>" title="">
						<?php if($file['type'] == 'dir'):?>
							<strong><?php echo htmlspecialchars($file['name']);?></strong>
						<?php else:?>
							<?php echo htmlspecialchars($file['basename']);?><span class='extention'><?php echo $file['extention'];?></span>
						<?php endif;?>
					</a>
				</td>
				<td class="filesize" title="<?php echo human_file_size($file['size']); ?>" style="color:rgb(<?php echo $simple_size_color.','.$simple_size_color.','.$simple_size_color ?>)"><?php echo $simple_file_size; ?></td>
				<td class="date"><?php echo $file['date']; ?></td>
			</tr>
		<?php endforeach; ?>
