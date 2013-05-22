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
<!--
		<p>
				<label for="encryptAll"><?php p( $l->t( "Scan for unencrypted files and encrypt them" ) ); ?></label>
				<br />
				<em><?php p( $l->t( "Use this if you suspect that you still have files which are unencrypted, or encrypted using ownCloud 4 or older." ) ); ?></em>
				<br />
				<input type="submit" id="encryptAll" name="encryptAll" value="<?php p( $l->t( 'Scan and encrypt files' ) ); ?>" />
				<input type="password" name="userPassword" id="userPassword" />
				<label for="encryptAll"><?php p( $l->t( "Account password" ) ); ?></label>
				<div id="encryptAllSuccess"><?php p( $l->t( 'Scan complete' ) );?></div>
				<div id="encryptAllError"><?php p( $l->t( 'Unable to scan and encrypt files' ) );?></div>
		</p>
-->
	</fieldset>
</form>
