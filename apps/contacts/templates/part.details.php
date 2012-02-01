<?php if(array_key_exists('FN',$_['details'])): ?>
	<?php echo $this->inc('part.property.FN', array('property' => $_['details']['FN'][0])); ?>
	<?php echo $this->inc('part.property.N', array('property' => $_['details']['N'][0])); ?>
	<a href="export.php?contactid=<?php echo $_['id']; ?>"><img class="svg action" id="contacts_downloadcard" src="<?php echo image_path('', 'actions/download.svg'); ?>" title="<?php echo $l->t('Download contact');?>" /></a>
	<img class="svg action" id="contacts_deletecard" src="<?php echo image_path('', 'actions/delete.svg'); ?>" title="<?php echo $l->t('Delete contact');?>" />

	<?php if(isset($_['details']['PHOTO'])): // Emails first ?>
		<img id="contacts_details_photo" src="photo.php?id=<?php echo $_['id']; ?>">
	<?php endif; ?>

	<ul id="contacts_details_list">
		<?php if(isset($_['details']['BDAY'])): // Emails first ?>
			<?php echo $this->inc('part.property', array('property' => $_['details']['BDAY'][0])); ?>
		<?php endif; ?>

		<?php if(isset($_['details']['ORG'])): // Emails first ?>
			<?php echo $this->inc('part.property', array('property' => $_['details']['ORG'][0])); ?>
		<?php endif; ?>

		<?php foreach(array('EMAIL','TEL','ADR') as $type): ?>
			<?php if(isset($_['details'][$type])): // Emails first ?>
				<?php foreach($_['details'][$type] as $property): ?>
					<?php echo $this->inc('part.property',array('property' => $property )); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endforeach; ?>
		<li class="contacts_property_add">
			<form id="contacts_addpropertyform">
				<input type="hidden" name="id" value="<?php echo $_['id']; ?>">
				<p class="contacts_property_name">
					<select name="name" size="1">
						<?php echo html_select_options($_['property_types'], 'EMAIL') ?>
					</select>
					<br>
					<input id="contacts_addproperty_button" type="submit" value="<?php echo $l->t('Add'); ?>">
				</p>
				<p class="contacts_property_data" id="contacts_generic">
					<input type="text" name="value" value="">
				</p>
			</form>
			<div id="contacts_addcontactsparts" style="display:none;">
				<ul class="contacts_property_data" id="contacts_addresspart">
					<li>
						<label for="adr_type"><?php echo $l->t('Type'); ?></label>
						<select id="adr_type" name="parameters[TYPE]" size="1">
							<?php echo html_select_options($_['adr_types'], 'HOME') ?>
						</select>
					</li>
					<li>
						<label for="adr_pobox"><?php echo $l->t('PO Box'); ?></label>
						<input id="adr_pobox" type="text" name="value[0]" value="">
					</li>
					<li>
						<label for="adr_extended"><?php echo $l->t('Extended'); ?></label>
						<input id="adr_extended" type="text" name="value[1]" value="">
					</li>
					<li>
						<label for="adr_street"><?php echo $l->t('Street'); ?></label>
						<input id="adr_street" type="text" name="value[2]" value="">
					</li>
					<li>
						<label for="adr_city"><?php echo $l->t('City'); ?></label>
						<input id="adr_city" type="text" name="value[3]" value="">
					</li>
					<li>
						<label for="adr_region"><?php echo $l->t('Region'); ?></label>
						<input id="adr_region" type="text" name="value[4]" value="">
					</li>
					<li>
						<label for="adr_zipcode"><?php echo $l->t('Zipcode'); ?></label>
						<input id="adr_zipcode" type="text" name="value[5]" value="">
					</li>
					<li>
						<label for="adr_country"><?php echo $l->t('Country'); ?></label>
						<input id="adr_country" type="text" name="value[6]" value="">
					</li>
				</ul>
				<p class="contacts_property_data" id="contacts_phonepart">
					<input type="text" name="value" value="">
					<select name="parameters[TYPE][]" multiple="multiple" data-placeholder="<?php echo $l->t('Type') ?>">
						<?php echo html_select_options($_['phone_types'], 'CELL') ?>
					</select>
				</p>
				<p class="contacts_property_data" id="contacts_generic">
					<input type="text" name="value" value="">
				</p>
			</div>
		</li>
	</ul>
<?php endif; ?>
<script language="Javascript">
/* Re-tipsify ;-)*/
	$('#contacts_deletecard').tipsy({gravity: 'ne'});
	$('#contacts_downloadcard').tipsy({gravity: 'ne'});
	$('.button').tipsy();
</script>
