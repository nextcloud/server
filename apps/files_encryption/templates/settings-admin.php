<form id="encryption">
	<fieldset class="personalblock">

		<h2><?php p($l->t('Encryption')); ?></h2>

		<p>
			<?php p($l->t("Enable recovery key (allow to recover users files in case of password loss):")); ?>
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
				name='adminEnableRecovery'
				value='1'
				<?php echo($_["recoveryEnabled"] === '1' ? 'checked="checked"' : 'disabled'); ?> />
			<?php p($l->t("Enabled")); ?>
			<br/>

			<input
				type='radio'
				name='adminEnableRecovery'
				value='0'
				<?php echo($_["recoveryEnabled"] === '0' ? 'checked="checked"' : 'disabled'); ?> />
			<?php p($l->t("Disabled")); ?>
		</p>
		<br/><br/>

		<p name="changeRecoveryPasswordBlock" <?php if ($_['recoveryEnabled'] === '0') print_unescaped('class="hidden"');?>>
			<strong><?php p($l->t("Change recovery key password:")); ?></strong>
			<br/><br/>
			<input
				type="password"
				name="changeRecoveryPassword"
				id="oldEncryptionRecoveryPassword"
			<label for="oldEncryptionRecoveryPassword"><?php p($l->t("Old Recovery key password")); ?></label>
			<br/>
			<br/>
			<input
				type="password"
				name="changeRecoveryPassword"
				id="newEncryptionRecoveryPassword"
			<label for="newEncryptionRecoveryPassword"><?php p($l->t("New Recovery key password")); ?></label>
			<br/>
			<input
				type="password"
				name="changeRecoveryPassword"
				id="repeatedNewEncryptionRecoveryPassword"
			<label for="repeatEncryptionRecoveryPassword"><?php p($l->t("Repeat New Recovery key password")); ?></label>
			<br/>
			<button
				type="button"
				name="submitChangeRecoveryKey"
				disabled><?php p($l->t("Change Password")); ?>
			</button>
			<span class="msg"></span>
		</p>
	</fieldset>
</form>
