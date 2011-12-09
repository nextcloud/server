<?php foreach( $_['contacts'] as $contact ): ?>
	<li data-id="<?php echo $contact['id']; ?>"><?php echo $contact['addressbookid']; ?> - <a href="index.php?id=<?php echo $contact['id']; ?>"><?php echo $contact['fullname']; ?></a> </li>
<?php endforeach; ?>
