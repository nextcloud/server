<script type='text/javascript'>
	var totalurl = '<?php echo OC_Helper::linkToAbsolute('contacts', 'carddav.php'); ?>/addressbooks';
	var categories = <?php echo json_encode($_['categories']); ?>;
	var lang = '<?php echo OC_Preferences::getValue(OC_User::getUser(), 'core', 'lang', 'en'); ?>';
</script>
<div id="controls">
	<form>
		<input type="button" id="contacts_newcontact" value="<?php echo $l->t('Add Contact'); ?>">
		<input type="button" id="chooseaddressbook" value="<?php echo $l->t('Addressbooks'); ?>">
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
			echo $this->inc('part.contact');
		}
		else{
			echo $this->inc('part.no_contacts');
		}
	?>
</div>
<!-- Dialogs -->
<div id="dialog_holder"></div>
<!-- End of Dialogs -->
