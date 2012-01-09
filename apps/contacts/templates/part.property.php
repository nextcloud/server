<li class="contacts_property" data-checksum="<?php echo $_['property']['checksum']; ?>">
	<?php if($_['property']['name'] == 'BDAY'): ?>
		<p class="contacts_property_name"><?php echo $l->t('Birthday'); ?></p>
		<p class="contacts_property_data">
			<?php echo $l->l('date',new DateTime($_['property']['value'])); ?>
			<span style="display:none;" data-use="delete"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></span>
		</p>
	<?php elseif($_['property']['name'] == 'ORG'): ?>
		<p class="contacts_property_name"><?php echo $l->t('Organization'); ?></p>
		<p class="contacts_property_data">
			<?php echo $_['property']['value']; ?>
			<span style="display:none;" data-use="edit"><img class="svg action" src="<?php echo image_path('', 'actions/rename.svg'); ?>" /></span>
			<span style="display:none;" data-use="delete"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></span>
		</p>
	<?php elseif($_['property']['name'] == 'EMAIL'): ?>
		<p class="contacts_property_name"><?php echo $l->t('Email'); ?></p>
		<p class="contacts_property_data">
			<?php echo $_['property']['value']; ?>
			<span style="display:none;" data-use="edit"><img class="svg action" src="<?php echo image_path('', 'actions/rename.svg'); ?>" /></span>
			<span style="display:none;" data-use="delete"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></span>
		</p>
	<?php elseif($_['property']['name'] == 'TEL'): ?>
		<p class="contacts_property_name"><?php echo (isset($_['property']['parameters']['PREF']) && $_['property']['parameters']['PREF']) ? $l->t('Preferred').' ' : '' ?><?php echo $l->t('Phone'); ?></p>
		<p class="contacts_property_data">
			<?php echo $_['property']['value']; ?>
			<?php if(isset($_['property']['parameters']['TYPE']) && !empty($_['property']['parameters']['TYPE'])): ?>
<?php
	foreach($_['property']['parameters']['TYPE'] as $type) {
		if (isset($_['phone_types'][strtoupper($type)])){
			$types[]=$_['phone_types'][strtoupper($type)];
		}
		else{
			$types[]=$l->t(ucwords(strtolower($type)));
		}
	}
	$label = join(' ', $types);
?>
				(<?php echo $label; ?>)
			<?php endif; ?>
			<span style="display:none;" data-use="edit"><img class="svg action" src="<?php echo image_path('', 'actions/rename.svg'); ?>" /></span>
			<span style="display:none;" data-use="delete"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></span>
		</p>
	<?php elseif($_['property']['name'] == 'ADR'): ?>
		<p class="contacts_property_name">
			<?php echo $l->t('Address'); ?>
			<?php if(isset($_['property']['parameters']['TYPE'])): ?>
				<br>
<?php
	$type = $_['property']['parameters']['TYPE'];
	if (isset($_['adr_types'][strtoupper($type)])){
		$label=$_['adr_types'][strtoupper($type)];
	}
	else{
		$label=$l->t(ucwords(strtolower($type)));
	}
?>
				(<?php echo $label; ?>)
			<?php endif; ?>
		</p>
		<p class="contacts_property_data">
			<?php if(!empty($_['property']['value'][0])): ?>
				<?php echo $_['property']['value'][0]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][1])): ?>
				<?php echo $_['property']['value'][1]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][2])): ?>
				<?php echo $_['property']['value'][2]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][3])): ?>
				<?php echo $_['property']['value'][3]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][4])): ?>
				<?php echo $_['property']['value'][4]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][5])): ?>
				<?php echo $_['property']['value'][5]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][6])): ?>
				<?php echo $_['property']['value'][6]; ?>
			<?php endif; ?>
			<span style="display:none;" data-use="edit"><img class="svg action" src="<?php echo image_path('', 'actions/rename.svg'); ?>" /></span>
			<span style="display:none;" data-use="delete"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></span>
		</p>
	<?php endif; ?>
</li>
