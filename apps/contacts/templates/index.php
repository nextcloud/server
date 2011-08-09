<?php // Include Style and Script
OC_Util::addScript('contacts','interface');
OC_Util::addStyle('contacts','styles');
?>

<?php
/*<div class="contacts_addressbooks">
	<div class="contacts_addressbooksexpander">
		Addressbooks
	</div>
	<div class="contacts_addressbooksdetails" style="display:none;">
		<?php foreach($_['addressbooks'] as $addressbook): ?>
			<?php echo $addressbook['displayname']; ?>: <?php echo $addressbook['description']; ?><br>
		<?php endforeach; ?>
		<br>To use this addressbook, use .../apps/contacts/carddav.php/addressbooks/USERNAME/addressbookname.php
	</div>
</div>*/
?>
<div id="contacts_contacts" class="leftcontent">
	<ul>
		<?php echo $this->inc("part.contacts"); ?>
	</ul>
	<a id="contacts_newcontact"><?php echo $l->t('Add Contact'); ?></a>
</div>
<div id="contacts_details" class="rightcontent" data-id="<?php echo $_['id']; ?>">
	<?php echo $this->inc("part.details"); ?>
</div>
