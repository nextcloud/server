<input type="hidden" id="bookmarkFilterTag" value="<?php if(isset($_GET['tag'])) echo htmlentities($_GET['tag']); ?>" />
<h2 class="bookmarks_headline"><?php echo isset($_GET["tag"]) ? 'Bookmarks with tag: ' . urldecode($_GET["tag"]) : 'All bookmarks'; ?></h2>
<div class="bookmarks_menu">
	<input type="button" class="bookmarks_addBtn" value="Add Bookmark"/>&nbsp;
	<a class="bookmarks_addBml" href="javascript:var url = encodeURIComponent(location.href);window.open('<?php echo (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . OC_Helper::linkTo('bookmarks', 'addBm.php'); ?>?url='+url, 'owncloud-bookmarks');" title="Drag this to your browser bookmarks and click it, when you want to bookmark a webpage.">Add page to ownCloud</a>
</div>
<div class="bookmarks_add">
	<p><label class="bookmarks_label">Address</label><input type="text" id="bookmark_add_url" class="bookmarks_input" /></p>
	<p><label class="bookmarks_label">Title</label><input type="text" id="bookmark_add_title" class="bookmarks_input" />
       <img class="loading_meta" src="<?php echo OC_Helper::imagePath('core', 'loading.gif'); ?>" /></p>
	<p><label class="bookmarks_label">Description</label><input type="text" id="bookmark_add_description" class="bookmarks_input" />
       <img class="loading_meta" src="<?php echo OC_Helper::imagePath('core', 'loading.gif'); ?>" /></p>
	<p><label class="bookmarks_label">Tags</label><input type="text" id="bookmark_add_tags" class="bookmarks_input" /></p>
	<p><label class="bookmarks_label"> </label><label class="bookmarks_hint">Hint: Use space to separate tags.</label></p>
	<p><label class="bookmarks_label"></label><input type="submit" id="bookmark_add_submit" /></p>
</div>
<div class="bookmarks_sorting pager">
	<ul>
		<li class="bookmarks_sorting_recent">Recent Bookmarks</li>
		<li class="bookmarks_sorting_clicks">Most clicks</li>
	</ul>
</div>
<div class="clear"></div>
<div class="bookmarks_list">
	<noscript>
	JavaScript is needed to display your Bookmarks
	</noscript>
	You have no bookmarks
</div>
