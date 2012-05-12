<?php 
$id = $_['id'];
$wattr = isset($_['width'])?'width="'.$_['width'].'"':'';
$hattr = isset($_['height'])?'height="'.$_['height'].'"':'';
$rand = isset($_['refresh'])?'&refresh='.rand():'';
?>
<ul id="phototools" class="transparent hidden">
	<li><a class="svg delete" title="<?php echo $l->t('Delete current photo'); ?>"></a></li>
	<li><a class="svg edit" title="<?php echo $l->t('Edit current photo'); ?>"></a></li>
	<li><a class="svg upload" title="<?php echo $l->t('Upload new photo'); ?>"></a></li>
	<li><a class="svg cloud" title="<?php echo $l->t('Select photo from ownCloud'); ?>"></a></li>
</ul>
<img class="loading" id="contacts_details_photo" <?php echo $wattr; ?> <?php echo $hattr; ?> src="<?php echo OCP\Util::linkToAbsolute('contacts', 'photo.php'); ?>?id=<?php echo $id.$rand; ?>" />
<progress id="contacts_details_photo_progress" style="display:none;" value="0" max="100">0 %</progress>


