Name <?php echo $_['details']['FN'][0]['value']; ?>
<?php if(array_key_exists('PHOTO',$_['details'])): ?>
	<img src="photo.php?id=<?php echo $_['id']; ?>">
<?php endif; ?>