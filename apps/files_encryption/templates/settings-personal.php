<form id="encryption">
	<fieldset class="personalblock">
		<legend>
			<?php p( $l->t( 'Encryption' ) ); ?>
		</legend>
		
		<?php if ( $_["recoveryEnabled"] ): ?>
			<p>
				<label for="userEnableRecovery"><?php p( $l->t( "Enable password recovery by sharing all files with your administrator:" ) ); ?></label>
				<br />
				<em><?php p( $l->t( "Enabling this option will allow you to reobtain access to your encrypted files if your password is lost" ) ); ?></em>
				<br />
				<input 
				type='radio'
				name='userEnableRecovery'
				value='1'
				<?php echo ( $_["recoveryEnabledForUser"] == 1 ? 'checked="checked"' : '' ); ?> />
				<?php p( $l->t( "Enabled" ) ); ?>
				<br />
				
				<input 
				type='radio'
				name='userEnableRecovery'
				value='0'
				<?php echo ( $_["recoveryEnabledForUser"] == 0 ? 'checked="checked"' : '' ); ?> />
				<?php p( $l->t( "Disabled" ) ); ?>
				<div id="recoveryEnabledSuccess"><?php p( $l->t( 'File recovery settings updated' ) ); ?></div>
				<div id="recoveryEnabledError"><?php p( $l->t( 'Could not update file recovery' ) ); ?></div>
			</p>
		<?php endif; ?>
		<br />
	</fieldset>
</form>
