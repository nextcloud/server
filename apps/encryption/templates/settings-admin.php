<?php
/** @var array $_ */
/** @var OC_L10N $l */
script('encryption', 'settings-admin');
script('core', 'multiselect');
?>
<form id="ocDefaultEncryptionModule" class="sub-section">
	<?php if(!$_["initStatus"]): ?>
		<?php p($l->t("Encryption App is enabled but your keys are not initialized, please log-out and log-in again")); ?>
	<?php else: ?>
	<p id="encryptionSetRecoveryKey">
		<?php p($l->t('Enable recovery key: ')); ?>
		<span class="msg"></span>
		<br/>
		<em>
		<?php p($l->t("The recovery key is an extra encryption key that is used
		to encrypt files. It allows recovery of a user's files if the user forgets their password.")) ?>
		</em>
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
		<label for="adminEnableRecovery"><?php p($l->t("Enable recovery key")); ?></label>
		<br/>

		<input
			type='radio'
			id='adminDisableRecovery'
			name='adminEnableRecovery'
			value='0'
			<?php echo($_["recoveryEnabled"] === '0' ? 'checked="checked"' : ''); ?> />
		<label for="adminDisableRecovery"><?php p($l->t("Disable recovery key")); ?></label>
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
