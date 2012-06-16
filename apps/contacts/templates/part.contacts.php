<?php
// get the names of the addressbooks
$addressbook_names = OC_Contacts_Addressbook::all(OCP\USER::getUser());
$contacts = array();

// sort the contacts by addressbookid
foreach( $_['contacts'] as $contact ):
	if(is_null($contacts[$contact['addressbookid']])) {
		$contacts[$contact['addressbookid']] = array();
	}
	$contacts[$contact['addressbookid']][] = $contact;
endforeach;

// print them out sorted by addressbook-name
for($i=0; $i<count($addressbook_names); $i++) {
	// a little ugly doing it this way but dunno how to do it else :)
	if(!(is_null($contacts[$addressbook_names[$i]['id']]))) { // look if we got contacts from this adressbook
		echo '<h3 class="addressbookname">'.$addressbook_names[$i]['displayname'].'</h3>';
		echo '<div>
			<ul class="contacts">';
		foreach($contacts[$addressbook_names[$i]['id']] as $contact):
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
		echo '</ul></div>';
	}
}
?>
<script language="Javascript">
$(document).ready(function() {
	$('#leftcontent .addressbookname').click(function(event) {
		$(this).next().toggle('slow');
		return false;
	}).next().hide();
});
</script>
