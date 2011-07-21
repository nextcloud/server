<form id="quota">
	<fieldset>
		<legend>Music Directories</legend>
		<ul id='folderlist'>
			<?php foreach($_['folders'] as $folder):?>
				<li>
					<?php echo $folder['name'];?>
					<span class='right'>
						<?php echo $folder['songs'];?> songs
						<button class='rescan prettybutton'>Rescan</button>
						<button class='delete prettybutton'>Delete</button>
					</span>
				</li>
			<?php endforeach; ?>
			<li>
				<input placeholder='path' id='scanpath'/>
				<span class='right'><button class='scan prettybutton'>Scan</button></span>
			</li>
		</ul>
		<label for="autoupdate" title='Automaticaly scan new files in above directories'>Auto Update</label>
		<input type='checkbox' <?php if($_['autoupdate']){echo 'checked="checked"';};?> id='autoupdate' title='Automaticaly scan new files in above directories'>
	</fieldset>
</form>