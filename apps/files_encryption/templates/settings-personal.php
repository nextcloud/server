<form id="encryption">
	<fieldset class="personalblock">
		<strong><?php echo $l->t('Choose encryption mode:'); ?></strong>
		<p>
			<input 
			type="hidden" 
			name="prev_encryption_mode" 
			id="prev_encryption_mode" 
			value="<?php echo $_['encryption_mode']; ?>"
			>
			
			<input 
			type="radio" 
			name="encryption_mode" 
			value="client" 
			id='client_encryption' 
			style="width:20px;" 
			<?php if ($_['encryption_mode'] == 'client') echo "checked='checked'"?>
			/> 
			<?php echo $l->t('Client side encryption (most secure but makes it impossible to access your data from the web interface)'); ?>
			<br />
			
			<input 
			type="radio" 
			name="encryption_mode" 
			value="server" 
			id='server_encryption' 
			style="width:20px;" <?php if ($_['encryption_mode'] == 'server') echo "checked='checked'"?>
			/> 
			<?php echo $l->t('Server side encryption (allows you to access your files from the web interface and the desktop client)'); ?>
			<br />
			
			<input 
			type="radio" 
			name="encryption_mode" 
			value="none" 
			id='none_encryption' 
			style="width:20px;" 
			<?php if ($_['encryption_mode'] == 'none') echo "checked='checked'"?>
			/> 
			<?php echo $l->t('None (no encryption at all)'); ?>
			<br/>
		</p>
	</fieldset>
</form>
