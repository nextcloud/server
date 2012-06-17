<?php
foreach($_['books'] as $id => $addressbook) {
	echo '<h3 class="addressbook" data-id="'.$id.'">'.$addressbook['displayname'].'</h3>';
	echo '<ul class="contacts hidden">';
	foreach($addressbook['contacts'] as $contact) {
		echo '<li role="button" book-id="'.$contact['addressbookid'].'" data-id="'.$contact['id'].'"><a href="index.php?id='.$contact['id'].'">'.$contact['displayname'].'</a></li>';
	}
	echo '</ul>';
}
?>
