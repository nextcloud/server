<?php // Include Style and Script
OC_Util::addScript('contacts','interface');
OC_Util::addStyle('contacts','styles');
?>

<div id="leftcontent" class="leftcontent">
	<ul>
		<?php echo $this->inc("part.contacts"); ?>
	</ul>
	<a id="contacts_newcontact"><?php echo $l->t('Add Contact'); ?></a>
</div>
<div id="rightcontent" class="rightcontent" data-id="<?php echo $_['id']; ?>">
	<?php echo $this->inc("part.details"); ?>
</div>
<?php if(count($_['addressbooks']) == 1 ): ?>
	<?php echo $l->t('The path to this addressbook is %s', array(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].OC::$WEBROOT.'/apps/contacts/carddav.php/addressbooks/'.OC_User::getUser().'/'.$_['addressbooks'][0]['uri'])); ?>
<?php endif; ?>
