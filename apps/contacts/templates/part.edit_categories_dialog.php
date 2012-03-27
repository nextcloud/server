<?php
$categories = isset($_['categories'])?$_['categories']:array();
?>
<div id="edit_categories_dialog" title="<?php echo $l->t('Edit categories'); ?>">
<!-- ?php print_r($types); ? -->
	<form method="post" id="categoryform">
	<div class="scrollarea">
	<ul id="categorylist">
	<?php foreach($categories as $category) { ?>
	<li><input type="checkbox" name="categories[]" value="<?php echo $category; ?>" /><?php echo $category; ?></li>
	<?php } ?>
	</ul>
	</div>
	<div class="bottombuttons"><input type="text" id="category_addinput" name="category" /><button id="category_addbutton" disabled="disabled"><?php echo $l->t('Add'); ?></button></div>
	</form>
</div>
