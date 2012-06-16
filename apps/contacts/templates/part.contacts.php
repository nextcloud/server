<?php foreach( $_['contacts'] as $contact ):
	$display = trim($contact['fullname']);
	if(!$display) {
		$vcard = OC_Contacts_App::getContactVCard($contact['id']);
		if(!is_null($vcard)) {
			$struct = OC_Contacts_VCard::structureContact($vcard);
			$display = isset($struct['EMAIL'][0])?$struct['EMAIL'][0]['value']:'[UNKNOWN]';
		}
	}
?>
	<li role="button" book-id="<?php echo $contact['addressbookid']; ?>" data-id="<?php echo $contact['id']; ?>"><a href="index.php?id=<?php echo $contact['id']; ?>"><?php echo htmlspecialchars($display); ?></a></li>
<?php endforeach; ?>
