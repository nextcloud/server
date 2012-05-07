<?php
$adr = isset($_['adr'])?$_['adr']:array();
$id = $_['id'];
$types = array();
foreach(isset($adr['parameters']['TYPE'])?array($adr['parameters']['TYPE']):array() as $type) {
	$types[] = strtoupper($type);
}
?>
<div id="edit_address_dialog" title="<?php echo $l->t('Edit address'); ?>">
<!-- ?php print_r($types); ? -->
	<fieldset id="address">
		<dl class="form">
			<dt>
				<label class="label" for="adr_type"><?php echo $l->t('Type'); ?></label>
			</dt>
			<dd>
				<select id="adr_type" name="parameters[ADR][TYPE]" size="1">
					<?php echo OCP\html_select_options($_['adr_types'], $types) ?>
				</select>
			</dd>
			<dt>
				<label class="label" for="adr_pobox"><?php echo $l->t('PO Box'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_pobox" name="value[ADR][0]" placeholder="<?php echo $l->t('PO Box'); ?>" value="<?php echo isset($adr['value'][0])?$adr['value'][0]:''; ?>">
			</dd>
			<dd>
			<dt>
				<label class="label" for="adr_extended"><?php echo $l->t('Extended'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_extended" name="value[ADR][1]" placeholder="<?php echo $l->t('Extended'); ?>" value="<?php echo isset($adr['value'][1])?$adr['value'][1]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_street"><?php echo $l->t('Street'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_street" name="value[ADR][2]" placeholder="<?php echo $l->t('Street'); ?>" value="<?php echo isset($adr['value'][2])?$adr['value'][2]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_city"><?php echo $l->t('City'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_city" name="value[ADR][3]" placeholder="<?php echo $l->t('City'); ?>" value="<?php echo isset($adr['value'][3])?$adr['value'][3]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_region"><?php echo $l->t('Region'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_region" name="value[ADR][4]" placeholder="<?php echo $l->t('Region'); ?>" value="<?php echo isset($adr['value'][4])?$adr['value'][4]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_zipcode"><?php echo $l->t('Zipcode'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_zipcode" name="value[ADR][5]" placeholder="<?php echo $l->t('Zipcode'); ?>" value="<?php echo isset($adr['value'][5])?$adr['value'][5]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_country"><?php echo $l->t('Country'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_country" name="value[ADR][6]" placeholder="<?php echo $l->t('Country'); ?>" value="<?php echo isset($adr['value'][6])?$adr['value'][6]:''; ?>">
			</dd>
		</dl>
	</fieldset>
	</form>
</div>
