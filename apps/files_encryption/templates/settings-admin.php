<form id="encryption">
	<fieldset class="personalblock">
		
		<p>
			<strong><?php p($l->t( 'Encryption' )); ?></strong>
			<br />
		</p>
		<p>
			<?php p($l->t( "Enable encryption passwords recovery key (allow sharing to recovery key):" )); ?>
			<br />
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
