<?php
foreach($_['contacts'] as $category => $contacts):
	echo '<h3 class="addressbookname">'.$category.'</h3>';
	echo '<div>';
	echo '<ul class="contacts">';
	foreach($contacts as $contact):
		$display = trim($contact['fullname']);
		if(!$display) {
			$vcard = OC_Contacts_App::getContactVCard($contact['id']);
			if(!is_null($vcard)) {
				$struct = OC_Contacts_VCard::structureContact($vcard);
				$display = isset($struct['EMAIL'][0])?$struct['EMAIL'][0]['value']:'[UNKNOWN]';
			}
		}
		echo '<li role="button" book-id="'.$contact['addressbookid'].'" data-id="'.$contact['id'].'"><a href="index.php?id='.$contact['id'].'">'.htmlspecialchars($display).'</a></li>';
	endforeach;
	echo '</ul>';
	echo '</div>';
endforeach;
?>
<script language="Javascript">
$(document).ready(function() {
	$('#leftcontent .addressbookname').click(function(event) {
		$(this).next().toggle('slow');
		return false;
	}).next().hide();
});
</script>
