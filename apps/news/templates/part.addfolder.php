
<div id="addfolder_dialog" title="<?php echo $l->t("Add Folder"); ?>">
<table width="100%" style="border: 0;">
<tr>
	<td>Add new folder</td>
	<td>
		<div class="add_parentfolder">
			<button id="dropdownBtn" onclick="News.DropDownMenu.dropdown(this)">
			    <?php echo $l->t('EVERYTHING'); ?>
			</button>
			<input id="inputfolderid" type="hidden" name="folderid" value="0" />
			<ul class="menu" id="dropdownmenu">
				<?php echo $this->inc("part.folderlist"); ?>
			</ul>
		</div>
	</td>
</tr>
<tr>
	<td><input type="text" id="folder_add_name" placeholder="<?php echo $l->t('Folder name'); ?>" class="news_input" /></td>
	<td><input type="submit" value="<?php echo $l->t('Add folder'); ?>" onclick="News.Folder.submit(this)" id="folder_add_submit" /></td>
</tr>
</table>