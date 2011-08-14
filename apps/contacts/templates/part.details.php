<?php if(array_key_exists('PHOTO',$_['details'])): ?>
	<img src="photo.php?id=<?php echo $_['id']; ?>">
<?php endif; ?>

<?php if(array_key_exists('FN',$_['details'])): ?>
	<?php echo $this->inc('part.property', array('property' => $_['details']['FN'][0])); ?>
<?php endif; ?>

<?php if(isset($_['details']['BDAY'])): // Emails first ?>
	<?php echo $this->inc('part.property', array('property' => $_['details']['BDAY'][0])); ?>
<?php endif; ?>

<?php if(isset($_['details']['ORG'])): // Emails first ?>
	<?php echo $this->inc('part.property', array('property' => $_['details']['ORG'][0])); ?>
<?php endif; ?>

<?php foreach(array('EMAIL','TEL','ADR') as $type): ?>
	<?php if(isset($_['details'][$type])): // Emails first ?>
		<br>
		<?php foreach($_['details'][$type] as $property): ?>
			<?php echo $this->inc('part.property',array('property' => $property )); ?>
		<?php endforeach; ?>
	<?php endif; ?>
<?php endforeach; ?>

<div id="contacts_cardoptions">
	<a id="contacts_deletecard"><img class="svg action" src="<?php echo image_path('', 'actions/delete.svg'); ?>" /></a>
	<a id="contacts_addproperty"><img class="svg action" src="<?php echo image_path('', 'actions/download.svg'); ?>" /></a>
</div>
