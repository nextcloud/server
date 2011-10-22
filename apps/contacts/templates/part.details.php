<?php if(array_key_exists('FN',$_['details'])): ?>
	<p id="contacts_details_name"><?php echo $_['details']['FN'][0]['value']; ?></p>

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
	</ul>
	<form>
		<img class="svg action" id="contacts_deletecard" src="<?php echo image_path('', 'actions/delete.svg'); ?>" title="<?php echo $l->t('Delete contact');?>" />
		<input type="button" id="contacts_addproperty" value="<?php echo $l->t('Add Property');?>">
	</form>
<?php endif; ?>
