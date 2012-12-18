<form id="calendar">
	<fieldset class="personalblock">
		<legend><strong><?php echo $l->t('Encryption');?></strong></legend>
		<input type='checkbox'<?php if ($_['encryption_enabled']): ?> checked="checked"<?php endif; ?>
			   id='enable_encryption' ></input>
		<label for='enable_encryption'><?php echo $l->t('Enable Encryption')?></label><br />
		<select id='encryption_blacklist' title="<?php echo $l->t('None')?>" multiple="multiple">
			<?php foreach ($_['blacklist'] as $type): ?>
				<option selected="selected" value="<?php echo $type;?>"><?php echo $type;?></option>
			<?php endforeach;?>
		</select><br />
		<?php echo $l->t('Exclude the following file types from encryption'); ?>
	</fieldset>
</form>
