<fieldset id="status_list" class="personalblock">
	<strong>Versioning and Backup</strong><br>
	<p><em>Please note: Backing up large files (around 16MB+) will cause your backup history to grow very large, very quickly.</em></p>
	<label class="bold">Backup Folder</label>
	<select name="file_versioning_head" id="file_versioning_head">
	<?php
        foreach ($_['commits'] as $commit):
            echo '<option value="' . $commit->sha() . '">' . $commit->message() . '</option>';
        endforeach;
	?>
	</select>
</fieldset>
