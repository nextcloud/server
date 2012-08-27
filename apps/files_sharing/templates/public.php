<input type="hidden" name="dir" value="<?php echo $_['dir'] ?>" id="dir">
<input type="hidden" name="downloadURL" value="<?php echo $_['downloadURL'] ?>" id="downloadURL">
<input type="hidden" name="filename" value="<?php echo $_['filename'] ?>" id="filename">
<input type="hidden" name="mimetype" value="<?php echo $_['mimetype'] ?>" id="mimetype">
<div id="preview">
	<p><?php echo $_['owner']; ?> shared the file <?php echo $_['name'] ?> with you</p>
</div>
<div id="content">
	<?php if (substr($_['mimetype'], 0 , strpos($_['mimetype'], '/')) == 'image'): ?>
		<img src="<?php echo $_['downloadURL']; ?>" />
	<?php endif; ?>
</div>
<a href="<?php echo $_['downloadURL']; ?>">Download</a>