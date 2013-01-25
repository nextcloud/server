<form id="encryption">
	<fieldset class="personalblock">
		
		<strong>
			<?php echo $l->t('Choose encryption mode:'); ?>
		</strong>
		
		<p>
			<i>
				<?php echo $l->t('Important: Once you selected an encryption mode there is no way to change it back'); ?>
			</i>
		</p>
		
		<p>
			<input 
			type="radio" 
			name="encryption_mode" 
			id="client_encryption" 
			value="client" 
			style="width:20px;" 
			<?php if ($_['encryption_mode'] == 'client') echo "checked='checked'"; if ($_['encryption_mode'] != 'none') echo "DISABLED"?> 
			/> 
			
			<?php echo $l->t("Client side encryption (most secure but makes it impossible to access your data from the web interface)"); ?>
			<br />
			
			<input
			type="radio" 
			name="encryption_mode" 
			id="server_encryption" 
			value="server" 
			style="width:20px;" 
			<?php if ($_['encryption_mode'] == 'server') echo "checked='checked'"; if ($_['encryption_mode'] != 'none') echo "DISABLED"?> 
			/> 
			
			<?php echo $l->t('Server side encryption (allows you to access your files from the web interface and the desktop client)'); ?>
			<br />
			
			<input
			type="radio" 
			name="encryption_mode" 
			id="user_encryption" 
			value="user" 
			style="width:20px;" 
			<?php if ($_['encryption_mode'] == 'user') echo "checked='checked'"; if ($_['encryption_mode'] != 'none') echo "DISABLED"?> 
			/>
			
			<?php echo $l->t('User specific (let the user decide)'); ?>
			<br/>
			
			<input
			type="radio" 
			name="encryption_mode" 
			id="none_encryption" 
			value="none" 
			style="width:20px;" 
			<?php if ($_['encryption_mode'] == 'none') echo "checked='checked'"; if ($_['encryption_mode'] != 'none') echo "DISABLED"?>
			/> 
			
			<?php echo $l->t('None (no encryption at all)'); ?>
			<br/>
			
		</p>
		<p>
			<strong><?php echo $l->t('Encryption'); ?></strong>
			
			<?php echo $l->t("Exclude the following file types from encryption"); ?>
			
			<select 
			id='encryption_blacklist' 
			title="<?php echo $l->t('None')?>" 
			multiple="multiple">
			<?php foreach($_["blacklist"] as $type): ?>
				<option selected="selected" value="<?php echo $type;?>"> <?php echo $type;?> </option>
			<?php endforeach;?>
			</select>
		</p>
	</fieldset>
</form>
