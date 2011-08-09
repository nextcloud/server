<?php // Include Style and Script
OC_Util::addScript('contacts','interface');
OC_Util::addStyle('contacts','styles');
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
