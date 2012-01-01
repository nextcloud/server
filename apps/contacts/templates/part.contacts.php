<?php foreach( $_['contacts'] as $contact ): ?>
	<li book-id="<?php echo $contact['addressbookid']; ?>" data-id="<?php echo $contact['id']; ?>"><a href="index.php?id=<?php echo $contact['id']; ?>"><?php echo $contact['fullname']; ?></a> </li>
<?php endforeach; ?>
