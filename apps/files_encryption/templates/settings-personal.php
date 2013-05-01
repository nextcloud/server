<form id="encryption">
	<fieldset class="personalblock">
		<legend>
			<?php p($l->t( 'Encryption' )); ?>
		</legend>
		
		<p>
			<?php p($l->t( 'File encryption is enabled.' )); ?>
		</p>
		<?php if ( ! empty( $_["blacklist"] ) ): ?>
		<p>
			<?php p($l->t( 'The following file types will not be encrypted:' )); ?>
		</p>
		
		<ul>
			<?php foreach( $_["blacklist"] as $type ): ?>
			<li>
				<?php p($type); ?>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
		
		<p>
			<?php p($l->t( "Enable password recovery by sharing all files with administrator:" )); ?>
			<br />
			<input 
			type='radio'
			name='userEnableRecovery'
			value='1'
			<?php echo ( $_["recoveryEnabled"] == 1 ? 'checked="checked"' : '' ); ?> />
			<?php p($l->t( "Enabled" )); ?>
			<br />
			
			<input 
			type='radio'
			name='userEnableRecovery'
			value='0'
			<?php echo ( $_["recoveryEnabled"] == 0 ? 'checked="checked"' : '' ); ?> />
			<?php p($l->t( "Disabled" )); ?>
		</p>
		
	</fieldset>
</form>
