<?php 
$id = $_['id'];
$wattr = isset($_['width'])?'width="'.$_['width'].'"':'';
$hattr = isset($_['height'])?'height="'.$_['height'].'"':'';
?>
<img class="loading" id="contacts_details_photo" <?php echo $wattr; ?> <?php echo $hattr; ?> src="<?php echo OC_Helper::linkToAbsolute('contacts', 'photo.php'); ?>?id=<?php echo $id; ?>&amp;refresh=<?php echo rand(); ?>" />
<progress id="contacts_details_photo_progress" style="display:none;" value="0" max="100">0 %</progress>


