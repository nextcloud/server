<?php
$adr = isset($_['adr'])?$_['adr']:array();
$id = isset($_['id'])?$_['id']:array();
$types = isset($_['types'])?$_['types']:array();
?>
<div id="edit_address_dialog" title="<?php echo $l->t('Edit address'); ?>">
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
				<input type="text" id="adr_pobox" name="value[ADR][0]" placeholder="<?php echo $l->t('PO Box'); ?>" value="<?php echo isset($adr[0])?$adr[0]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_street"><?php echo $l->t('Street address'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_street" name="value[ADR][2]" placeholder="<?php echo $l->t('Street and number'); ?>" value="<?php echo isset($adr[2])?$adr[2]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_extended"><?php echo $l->t('Extended'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_extended" name="value[ADR][1]" placeholder="<?php echo $l->t('Apartment number etc.'); ?>" value="<?php echo isset($adr[1])?$adr[1]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_city"><?php echo $l->t('City'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_city" name="value[ADR][3]" placeholder="<?php echo $l->t('City'); ?>" value="<?php echo isset($adr[3])?$adr[3]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_region"><?php echo $l->t('Region'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_region" name="value[ADR][4]" placeholder="<?php echo $l->t('E.g. state or province'); ?>" value="<?php echo isset($adr[4])?$adr[4]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_zipcode"><?php echo $l->t('Zipcode'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_zipcode" name="value[ADR][5]" placeholder="<?php echo $l->t('Postal code'); ?>" value="<?php echo isset($adr[5])?$adr[5]:''; ?>">
			</dd>
			<dt>
				<label class="label" for="adr_country"><?php echo $l->t('Country'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_country" name="value[ADR][6]" placeholder="<?php echo $l->t('Country'); ?>" value="<?php echo isset($adr[6])?$adr[6]:''; ?>">
			</dd>
		</dl>
		<div style="width: 100%; text-align:center;">Powered by <a href="http://geonames.org/" target="_blank">geonames.org</a></div>
	</fieldset>
	</form>
</div>
