<?php
	/** @var array $_ */
	/** @var OC_L10N $l */
?>
<form id="encryption" class="section">
	<h2><?php p($l->t('Server-side Encryption')); ?></h2>

	<?php if($_["initStatus"] === \OCA\Files_Encryption\Session::NOT_INITIALIZED): ?>
		<?php p($l->t("Encryption App is enabled but your keys are not initialized, please log-out and log-in again")); ?>
	<?php else: ?>
	<p id="encryptionSetRecoveryKey">
		<?php p($l->t("Enable recovery key (allow to recover users files in case of password loss):")); ?>
		<span class="msg"></span>
		<br/>
		<br/>
		<input type="password" name="encryptionRecoveryPassword" id="encryptionRecoveryPassword"/>
		<label for="recoveryPassword"><?php p($l->t("Recovery key password")); ?></label>
		<br/>
		<input type="password" name="encryptionRecoveryPassword" id="repeatEncryptionRecoveryPassword"/>
		<label for="repeatEncryptionRecoveryPassword"><?php p($l->t("Repeat Recovery key password")); ?></label>
		<br/>
		<input
			type='radio'
			id='adminEnableRecovery'
			name='adminEnableRecovery'
			value='1'
			<?php echo($_["recoveryEnabled"] === '1' ? 'checked="checked"' : ''); ?> />
		<label for="adminEnableRecovery"><?php p($l->t("Enabled")); ?></label>
		<br/>

		<input
			type='radio'
			id='adminDisableRecovery'
			name='adminEnableRecovery'
			value='0'
			<?php echo($_["recoveryEnabled"] === '0' ? 'checked="checked"' : ''); ?> />
		<label for="adminDisableRecovery"><?php p($l->t("Disabled")); ?></label>
	</p>
	<br/><br/>

	<p name="changeRecoveryPasswordBlock" id="encryptionChangeRecoveryKey" <?php if ($_['recoveryEnabled'] === '0') print_unescaped('class="hidden"');?>>
		<strong><?php p($l->t("Change recovery key password:")); ?></strong>
		<span class="msg"></span>
		<br/><br/>
		<input
			type="password"
			name="changeRecoveryPassword"
			id="oldEncryptionRecoveryPassword" />
		<label for="oldEncryptionRecoveryPassword"><?php p($l->t("Old Recovery key password")); ?></label>
		<br/>
		<br/>
		<input
			type="password"
			name="changeRecoveryPassword"
			id="newEncryptionRecoveryPassword" />
		<label for="newEncryptionRecoveryPassword"><?php p($l->t("New Recovery key password")); ?></label>
		<br/>
		<input
			type="password"
			name="changeRecoveryPassword"
			id="repeatedNewEncryptionRecoveryPassword" />
		<label for="repeatEncryptionRecoveryPassword"><?php p($l->t("Repeat New Recovery key password")); ?></label>
		<br/>
		<button
			type="button"
			name="submitChangeRecoveryKey">
				<?php p($l->t("Change Password")); ?>
		</button>
	</p>
	<?php endif; ?>
</form>
