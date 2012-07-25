
<div id="addfeed_dialog" title="<?php echo $l->t("Add Feed"); ?>">
<table width="100%" style="border: 0;">
<tr>
	<td>Add new feed</td>
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
	<td><input type="text" id="feed_add_url" placeholder="<?php echo $l->t('URL'); ?>" class="news_input" /></td>
	<td><input type="submit" value="<?php echo $l->t('Add feed'); ?>" onclick="News.Feed.submit(this)" id="feed_add_submit" /></td>
</tr>
</table>