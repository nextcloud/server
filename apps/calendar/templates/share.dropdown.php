<?php
if(array_key_exists('calid', $_)){
	$id = $_['calid'];
	$sharedelements = OC_Calendar_Share::allUsersSharedwith($_['calid'], OC_Calendar_Share::CALENDAR);
}else{
	$sharedelements = OC_Calendar_Share::allUsersSharedwith($_['eventid'], OC_Calendar_Share::EVENT);
	$id = $_['eventid'];
}
$users = array();$groups = array();$public = array();
foreach($sharedelements as $sharedelement){
	if($sharedelement['sharetype'] == 'user'){
		$users[] = $sharedelement;
	}elseif($sharedelement['sharetype'] == 'group'){
		$groups[] = $sharedelement;
	}elseif($sharedelement['sharetype'] == 'public'){
		$public = $sharedelement;
	}
}
?>
<strong><?php echo $l->t('Users');?>:</strong><br>
<select id="share_user" title="<?php echo $l->t('select users');?>" data-placeholder="<?php echo $l->t('select users'); ?>">
<option value=""></option>
<?php
$allocusers = OC_User::getUsers();
$allusers = array();
foreach($allocusers as $ocuser){
	$allusers[$ocuser] = $ocuser;
}
unset($allusers[OC_User::getUser()]);
$allusers = array_flip($allusers);
echo html_select_options($allusers, array());
?>
</select><br>
<ul id="sharewithuser_list">
<?php foreach($users as $user): ?>
	<li id="sharewithuser_<?php echo $user['share']; ?>"><input type="checkbox" width="12px" <?php echo ($user['permissions']?'checked="checked"':'')?> style="visibility:hidden;" title="<?php echo $l->t('Editable'); ?>"><?php echo $user['share']; ?><img src="<?php echo  OC::$WEBROOT; ?>/core/img/actions/delete.svg" class="svg action" style="display:none;float:right;"></li>
	<script>
		$('#sharewithuser_<?php echo $user['share']; ?> > img').click(function(){
			$('#share_user option[value="<?php echo $user['share']; ?>"]').removeAttr('disabled');
			Calendar.UI.Share.unshare(<?php echo $id; ?>, '<?php echo (array_key_exists('calid', $_)?'calendar':'event');?>', '<?php echo $user['share']; ?>', 'user');
			$('#sharewithuser_<?php echo $user['share']; ?>').remove();
			$("#share_user").trigger("liszt:updated");
		});
		$('#share_user option[value="<?php echo $user['share']; ?>"]').attr('disabled', 'disabled');
	</script>
<?php endforeach; ?>
</ul>
<strong><?php echo $l->t('Groups');?>:</strong><br>
<select id="share_group" title="<?php echo $l->t('select groups');?>" data-placeholder="<?php echo $l->t('select groups'); ?>">
<option value=""></option> 
<?php
$allocgroups = OC_Group::getGroups();
$allgroups = array();
foreach($allocgroups as $ocgroup){
	$allgroups[$ocgroup] = $ocgroup;
}
echo html_select_options($allgroups, array());
?>
</select><br>
<ul id="sharewithgroup_list">
<?php foreach($groups as $group): ?>
	<li id="sharewithgroup_<?php echo $group['share']; ?>"><input type="checkbox" width="12px" <?php echo ($group['permissions']?'checked="checked"':'')?> style="visibility:hidden;" title="<?php echo $l->t('Editable'); ?>"><?php echo $group['share']; ?><img src="<?php echo  OC::$WEBROOT; ?>/core/img/actions/delete.svg" class="svg action" style="display:none;float:right;"></li>
	<script>
		$('#sharewithgroup_<?php echo $group['share']; ?> > img').click(function(){
			$('#share_group option[value="<?php echo $group['share']; ?>"]').removeAttr('disabled');
			Calendar.UI.Share.unshare(<?php echo $id; ?>, '<?php echo (array_key_exists('calid', $_)?'calendar':'event');?>, '<?php echo $group['share']; ?>', 'group'); ?>
			$('#sharewithgroup_<?php echo $group['share']; ?>').remove();
			$("#share_group").trigger("liszt:updated");
		});
		$('#share_group option[value="<?php echo $group['share']; ?>"]').attr('disabled', 'disabled');
	</script>
<?php endforeach; ?>
</ul>
<div id="public">
	<input type="checkbox" id="publish" <?php echo ($public['share'])?'checked="checked"':'' ?>><label for="publish"><?php echo $l->t('make public'); ?></label><br>
	<input type="text" id="public_token" value="<?php echo OC_Helper::linkToAbsolute('apps/calendar', 'share.php?t=' . $public['share'], null, true) ; ?>" onmouseover="$('#public_token').select();" style="<?php echo (!$public['share'])?'display:none':'' ?>">
</div>