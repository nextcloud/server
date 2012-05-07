<script type='text/javascript'>
	var totalurl = '<?php echo OCP\Util::linkToRemote('carddav'); ?>addressbooks';
	var categories = <?php echo json_encode($_['categories']); ?>;
	var lang = '<?php echo OCP\Config::getUserValue(OCP\USER::getUser(), 'core', 'lang', 'en'); ?>';
</script>
<div id="leftcontent" class="leftcontent">
	<ul id="contacts">
		<?php echo $this->inc("part.contacts"); ?>
	</ul>
</div>
<div id="bottomcontrols">
	<form>
		<button class="svg" id="contacts_newcontact" title="<?php echo $l->t('Add Contact'); ?>"><img class="svg" src="<?php echo OCP\Util::linkTo('contacts', 'img/contact-new.svg'); ?>" alt="<?php echo $l->t('Add Contact'); ?>"   /></button>
		<button class="svg" id="chooseaddressbook" title="<?php echo $l->t('Addressbooks'); ?>"><img class="svg" src="core/img/actions/settings.svg" alt="<?php echo $l->t('Addressbooks'); ?>" /></button>
	</form>
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
