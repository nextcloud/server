<tr class="contacts_details_property" data-checksum="<?php echo $_['property']['checksum']; ?>">
	<?php if($_['property']['name'] == 'FN'): ?>
		<td class="contacts_details_left"><?php echo $l->t('Name'); ?></td>
		<td class="contacts_details_right">
			<?php echo $_['property']['value']; ?>
			<span style="display:none;" data-use="edit"><img class="svg action" src="<?php echo image_path('', 'actions/rename.svg'); ?>" /></span>
		</td>
	<?php elseif($_['property']['name'] == 'BDAY'): ?>
		<td class="contacts_details_left"><?php echo $l->t('Birthday'); ?></td>
		<td class="contacts_details_right">
		<?php echo $l->l('date',new DateTime($_['property']['value'])); ?>
			<span style="display:none;" data-use="delete"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></span>
		</td>
	<?php elseif($_['property']['name'] == 'ORG'): ?>
		<td class="contacts_details_left"><?php echo $l->t('Organization'); ?></td>
		<td class="contacts_details_right">
			<?php echo $_['property']['value']; ?>
			<span style="display:none;" data-use="edit"><img class="svg action" src="<?php echo image_path('', 'actions/rename.svg'); ?>" /></span>
			<span style="display:none;" data-use="delete"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></span>
		</td>
	<?php elseif($_['property']['name'] == 'EMAIL'): ?>
		<td class="contacts_details_left"><?php echo $l->t('Email'); ?></td>
		<td class="contacts_details_right">
			<?php echo $_['property']['value']; ?>
			<span style="display:none;" data-use="edit"><img class="svg action" src="<?php echo image_path('', 'actions/rename.svg'); ?>" /></span>
			<span style="display:none;" data-use="delete"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></span>
		</td>
	<?php elseif($_['property']['name'] == 'TEL'): ?>
		<td class="contacts_details_left"><?php echo $l->t('Phone'); ?></td>
		<td class="contacts_details_right">
			<?php echo $_['property']['value']; ?>
			<?php if(isset($_['property']['parameters']['TYPE'])): ?>
				(<?php echo strtolower($_['property']['parameters']['TYPE']); ?>)
			<?php endif; ?>
			<span style="display:none;" data-use="edit"><img class="svg action" src="<?php echo image_path('', 'actions/rename.svg'); ?>" /></span>
			<span style="display:none;" data-use="delete"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></span>
		</td>
	<?php elseif($_['property']['name'] == 'ADR'): ?>
		<td class="contacts_details_left">
			<?php echo $l->t('Address'); ?>
			<?php if(isset($_['property']['parameters']['TYPE'])): ?>
				<br>
				(<?php echo strtolower($_['property']['parameters']['TYPE']); ?>)
			<?php endif; ?>
		</td>
		<td class="contacts_details_right">
			<?php if(!empty($_['property']['value'][0])): ?>
			<?php echo $l->t('PO Box'); ?> <?php echo $_['property']['value'][0]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][1])): ?>
			<?php echo $l->t('Extended'); ?> <?php echo $_['property']['value'][1]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][2])): ?>
			<?php echo $l->t('Street'); ?> <?php echo $_['property']['value'][2]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][3])): ?>
			<?php echo $l->t('City'); ?> <?php echo $_['property']['value'][3]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][4])): ?>
			<?php echo $l->t('Region'); ?> <?php echo $_['property']['value'][4]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][5])): ?>
			<?php echo $l->t('Zipcode'); ?> <?php echo $_['property']['value'][5]; ?><br>
			<?php endif; ?>
			<?php if(!empty($_['property']['value'][6])): ?>
			<?php echo $l->t('Country'); ?> <?php echo $_['property']['value'][6]; ?> 
			<?php endif; ?>
			<span style="display:none;" data-use="edit"><img class="svg action" src="<?php echo image_path('', 'actions/rename.svg'); ?>" /></span>
			<span style="display:none;" data-use="delete"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></span>
		</td>
	<?php endif; ?>
</tr>
