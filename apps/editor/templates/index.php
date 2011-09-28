<div id="controls">
	<form>
		<input type="button" id="editor_save" value="<?php echo $l->t('Save'); ?>">
        <input type="button" id="editor_close" onClick="window.history.back()" value="<?php echo $l->t('Close'); ?>">
	</form>
</div>
<div id="editor" data-type="<?php print_r( $_['filetype']); ?>" data-file="<?php echo $_['file']; ?>" data-dir="<?php echo $_['dir']; ?>"><?php echo urldecode($_['filecontents']); ?></div>
