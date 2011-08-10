<?php foreach( $_['contacts'] as $contact ): ?>
	<li data-id="<?php echo $contact['id']; ?>"><a href="index.php?id=<?php echo $contact['id']; ?>"><?php echo $contact['name']; ?></a> </li>
<?php endforeach; ?>
