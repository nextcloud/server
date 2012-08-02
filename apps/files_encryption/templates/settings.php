<form id="encryption">
	<fieldset class="personalblock">
		
	<strong>Choose encryption mode:</strong>
		
	<p>
	<input type="radio" name="encryption_mode" value="client" style="width:20px;" <?php if ($_['encryption_mode'] == 'client') echo "checked='checked'"?>/> Client side encryption (most secure but makes it impossible to access your data from the web interface)<br />
	<input type="radio" name="encryption_mode" value="server" style="width:20px;" <?php if ($_['encryption_mode'] == 'server') echo "checked='checked'"?> /> Server side encryption (allows you to access your files from the web interface and the desktop client)<br />
	<input type="radio" name="encryption_mode" value="user" style="width:20px;" <?php if ($_['encryption_mode'] == 'user') echo "checked='checked'"?>/> User specific (let the user decide)<br/>
	<input type="radio" name="encryption_mode" value="none" style="width:20px;" <?php if ($_['encryption_mode'] == 'none') echo "checked='checked'"?>/> None (no encryption at all)<br/>
	</p>	
	<p>	
	<strong><?php echo $l->t('Encryption'); ?></strong>
		<?php echo $l->t("Exclude the following file types from encryption"); ?>
		<select id='encryption_blacklist' title="<?php echo $l->t('None')?>" multiple="multiple">
			<?php foreach($_["blacklist"] as $type): ?>
				<option selected="selected" value="<?php echo $type;?>"><?php echo $type;?></option>
			<?php endforeach;?>
		</select>
	</p>		
	</fieldset>
</form>
