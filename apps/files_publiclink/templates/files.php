		<?php foreach($_["files"] as $file): ?>
			<tr>
				<td class="selection"><input type="checkbox" /></td>
				<td class="filename"><a style="background-image:url(<?php if($file["type"] == "dir") echo mimetype_icon("dir"); else echo mimetype_icon($file["mime"]); ?>)" href="<?php if($file["type"] == "dir") echo link_to("files_publiclink", "get.php?token=".$_['token']."&path=".$file["directory"]."/".$file["name"]); else echo link_to("files_publiclink", "get.php?token=".$_['token']."&path=".$file["directory"]."/".$file["name"]); ?>" title=""><?php echo htmlspecialchars($file["name"]); ?></a></td>
				<td class="filesize"><?php echo human_file_size($file["size"]); ?></td>
				<td class="date"><?php if($file["type"] != "dir") echo $file["date"]; ?></td>
				<td class="fileaction"><a href="" title="">▾</a></td>
			</tr>
		<?php endforeach; ?>
