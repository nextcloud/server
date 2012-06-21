<?php
foreach($_['books'] as $id => $addressbook) {
	echo '<h3 class="addressbook" data-id="'.$id.'">'.$addressbook['displayname'].'</h3>';
	echo '<ul class="contacts hidden" data-id="'.$id.'">';
	foreach($addressbook['contacts'] as $contact) {
		echo '<li role="button" data-bookid="'.$contact['addressbookid'].'" data-id="'.$contact['id'].'"><a href="'.link_to('contacts','index.php').'&id='.$contact['id'].'" style="background: url('.link_to('contacts','thumbnail.php').'?id='.$contact['id'].') no-repeat scroll 0 0 transparent;">'.$contact['displayname'].'</a></li>';
	}
	echo '</ul>';
}
?>
