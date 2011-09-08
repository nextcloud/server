<?php // Include Style and Script
OC_Util::addScript('contacts','interface');
OC_Util::addStyle('contacts','styles');
?>

<div id="controls">
	<form>
		<input type="button" id="contacts_newcontact" value="<?php echo $l->t('Add Contact'); ?>">
	</form>
</div>
<div id="leftcontent" class="leftcontent">
	<ul>
		<?php echo $this->inc("part.contacts"); ?>
	</ul>
</div>
<div id="rightcontent" class="rightcontent" data-id="<?php echo $_['id']; ?>">
	<?php echo $this->inc("part.details"); ?>
</div>
