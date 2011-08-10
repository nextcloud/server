<div class="contacts_property" data-checksum="<?php echo $_['property']['checksum']; ?>">
	<?php if($_['property']['name'] == 'FN'): ?>
		<div class="contacts_propertyname"><?php echo $l->t('Name'); ?></div>
		<div class="contacts_propertyvalue">
			<?php echo $_['property']['value']; ?>
			<span style="display:none;" data-use="edit"><img src="../../core/img/actions/rename.png"></span>
		</div>
	<?php elseif($_['property']['name'] == 'BDAY'): ?>
		<div class="contacts_propertyname"><?php echo $l->t('Birthday'); ?></div>
		<div class="contacts_propertyvalue">
		<?php echo $l->l('date',new DateTime($_['property']['value'])); ?>
			<span style="display:none;" data-use="delete"><img src="../../core/img/actions/delete.png"></span>
		</div>
	<?php elseif($_['property']['name'] == 'ORG'): ?>
		<div class="contacts_propertyname"><?php echo $l->t('Organisation'); ?></div>
		<div class="contacts_propertyvalue">
			<?php echo $_['property']['value']; ?>
			<span style="display:none;" data-use="edit"><img src="../../core/img/actions/rename.png"></span>
			<span style="display:none;" data-use="delete"><img src="../../core/img/actions/delete.png"></span>
		</div>
	<?php elseif($_['property']['name'] == 'EMAIL'): ?>
		<div class="contacts_propertyname"><?php echo $l->t('Email'); ?></div>
		<div class="contacts_propertyvalue">
			<?php echo $_['property']['value']; ?>
			<span style="display:none;" data-use="edit"><img src="../../core/img/actions/rename.png"></span>
			<span style="display:none;" data-use="delete"><img src="../../core/img/actions/delete.png"></span>
		</div>
	<?php elseif($_['property']['name'] == 'TEL'): ?>
		<div class="contacts_propertyname"><?php echo $l->t('Telephone'); ?></div>
		<div class="contacts_propertyvalue">
			<?php echo $_['property']['value']; ?>
			<?php if(isset($_['property']['parameters']['TYPE'])): ?>
				(<?php echo $l->t('tel_'.strtolower($_['property']['parameters']['TYPE'])); ?>)
			<?php endif; ?>
			<span style="display:none;" data-use="edit"><img src="../../core/img/actions/rename.png"></span>
			<span style="display:none;" data-use="delete"><img src="../../core/img/actions/delete.png"></span>
		</div>
	<?php elseif($_['property']['name'] == 'ADR'): ?>
		<div class="contacts_propertyname">
			<?php echo $l->t('Address'); ?>
			<?php if(isset($_['property']['parameters']['TYPE'])): ?>
				<br>
				(<?php echo $l->t('adr_'.strtolower($_['property']['parameters']['TYPE'])); ?>)
			<?php endif; ?>
		</div>
		<div class="contacts_propertyvalue">
			<?php echo $l->t('PO Box'); ?> <?php echo $_['property']['value'][0]; ?><br>
			<?php echo $l->t('Extended Address'); ?> <?php echo $_['property']['value'][1]; ?><br>
			<?php echo $l->t('Street Name'); ?> <?php echo $_['property']['value'][2]; ?><br>
			<?php echo $l->t('City'); ?> <?php echo $_['property']['value'][3]; ?><br>
			<?php echo $l->t('Region'); ?> <?php echo $_['property']['value'][4]; ?><br>
			<?php echo $l->t('Postal Code'); ?> <?php echo $_['property']['value'][5]; ?><br>
			<?php echo $l->t('Country'); ?> <?php echo $_['property']['value'][6]; ?> 
			<span style="display:none;" data-use="edit"><img src="../../core/img/actions/rename.png"></span>
			<span style="display:none;" data-use="delete"><img src="../../core/img/actions/delete.png"></span>
		</div>
	<?php endif; ?>
</div>
