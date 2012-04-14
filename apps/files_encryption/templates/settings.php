<form id="calendar">
	<fieldset class="personalblock">
	<strong><?php echo $l->t('Encryption'); ?></strong>
		<?php echo $l->t("Exclude the following file types from encryption"); ?>
		<select id='encryption_blacklist' title="<?php echo $l->t('None')?>" multiple="multiple">
			<?php foreach($_["blacklist"] as $type): ?>
				<option selected="selected" value="<?php echo $type;?>"><?php echo $type;?></option>
			<?php endforeach;?>
		</select>
	</fieldset>
</form>
