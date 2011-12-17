<?php // Include Style and Script
OC_Util::addScript('contacts','interface');
OC_Util::addScript('contacts','jquery.inview');
OC_Util::addStyle('contacts','styles');
OC_Util::addStyle('contacts','formtastic');
?>

<div id="controls">
	<form>
		<input type="button" id="contacts_newcontact" value="<?php echo $l->t('Add Contact'); ?>">
	</form>
</div>
<div id="leftcontent" class="leftcontent">
	<ul id="contacts">
		<?php echo $this->inc("part.contacts"); ?>
	</ul>
</div>
<div id="rightcontent" class="rightcontent" data-id="<?php echo $_['id']; ?>">
	<?php
		if ($_['id']){
			echo $this->inc("part.details");
		}
		else{
			echo $this->inc("part.addcardform");
		}
	?>
</div>
