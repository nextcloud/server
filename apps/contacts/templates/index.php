<?php // Include Style and Script
OC_Util::addScript('contacts','interface');
OC_Util::addStyle('contacts','styles');
?>

<div class="contacts_addressbooks">
	<div class="contacts_addressbooksexpander">
		Addressbooks
	</div>
	<div class="contacts_addressbooksdetails" style="display:none;">
		<?php foreach($_['addressbooks'] as $addressbook): ?>
			<?php echo $addressbook['displayname']; ?>: <?php echo $addressbook['description']; ?><br>
		<?php endforeach; ?>
		<br>To use this addressbook, use .../apps/contacts/carddav.php/addressbooks/USERNAME/addressbookname.php
	</div>
</div>
<div class="contacts_contacts leftcontent">
	<ul>
		<?php echo $this->inc("_contacts"); ?>
	</ul>
</div>
<div class="contacts_details rightcontent">
	<?php echo $this->inc("_details"); ?>
</div>
