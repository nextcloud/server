<?php // Include Style and Script
OC_Util::addScript('contacts','interface');
OC_Util::addStyle('contacts','styles');
?>

<div class="contacts_contacts leftcontent">
	<ul>
		<?php echo $this->inc("_contacts"); ?>
	</ul>
</div>
<div class="contacts_details rightcontent">
	<?php echo $this->inc("_details"); ?>
</div>
