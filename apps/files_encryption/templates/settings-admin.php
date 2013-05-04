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
			<strong>
				<?php p($l->t( "Enable encryption passwords recovery account (allow sharing to recovery account):" )); ?>
			<br />
			</strong>
			<?php p($l->t( "To perform a recovery log in using the 'recoveryAdmin' account and the specified password" )); ?>
			<br />
			<?php if ( empty( $_['recoveryAdminUid'] ) ): ?>
				<input type="password" name="recoveryPassword" id="recoveryPassword" />
				<label for="recoveryPassword">Recovery account password</label>
				<br />
			<?php endif; ?>
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
