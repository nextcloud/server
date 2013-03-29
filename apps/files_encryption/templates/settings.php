<form id="encryption">
	<fieldset class="personalblock">
		
		<p>
			<strong><?php p($l->t( 'Encryption' )); ?></strong>
			<br />
			
			<?php p($l->t( "Exclude the following file types from encryption:" )); ?>
			<br />
			
			<select 
			id='encryption_blacklist' 
			title="<?php p($l->t( 'None' ))?>" 
			multiple="multiple">
			<?php foreach($_["blacklist"] as $type): ?>
				<option selected="selected" value="<?php p($type); ?>"> <?php p($type); ?> </option>
			<?php endforeach;?>
			</select>
		</p>
		<p>
			<?php p($l->t( "Enable encryption passwords recovery account (allow sharing to recovery account):" )); ?>
			<br />
			<input 
			type='radio'
			name='adminEnableRecovery'
			value='1'
			<?php echo ( $_["recoveryEnabled"] == 1 ? 'checked="checked"' : '' ); ?> />
			<?php p($l->t( "Enabled" )); ?>
			<br />
			
			<input 
			type='radio'
			name='adminEnableRecovery'
			value='0'
			<?php echo ( $_["recoveryEnabled"] == 0 ? 'checked="checked"' : '' ); ?> />
			<?php p($l->t( "Disabled" )); ?>
		</p>
	</fieldset>
</form>
