<?php
$l=new OC_L10N('contacts');
$id = isset($_['id']) ? $_['id'] : '';
$card = array();
$card['id'] = $id;
$card['FN'] = (array_key_exists('FN',$_['details'])) ? $_['details']['FN'][0] : null;
$card['N'] = (array_key_exists('N',$_['details'])) ? $_['details']['N'][0] : array('', '', '', '', '');
$card['ORG'] = (array_key_exists('ORG',$_['details'])) ? $_['details']['ORG'][0] : null;
$card['PHOTO'] = (array_key_exists('PHOTO',$_['details'])) ? $_['details']['PHOTO'][0] : null;
$card['BDAY'] = (array_key_exists('BDAY',$_['details'])) ? $_['details']['BDAY'][0] : null;
if($card['BDAY']) {
	$bday = new DateTime($card['BDAY']['value']);
	$card['BDAY']['value'] = $bday->format('d-m-Y');
}
$card['NICKNAME'] = (array_key_exists('NICKNAME',$_['details'])) ? $_['details']['NICKNAME'][0] : null;
$card['EMAIL'] = (array_key_exists('EMAIL',$_['details'])) ? $_['details']['EMAIL'] : array();
$card['TEL'] = (array_key_exists('TEL',$_['details'])) ? $_['details']['TEL'] : array();
$card['ADR'] = (array_key_exists('ADR',$_['details'])) ? $_['details']['ADR'] : array();
?>
<div id="card">
	<div id="actionbar">
	<a id="contacts_propertymenu_button"></a>
	<ul id="contacts_propertymenu">
		<li><a data-type="PHOTO"><?php echo $l->t('Profile picture'); ?></a></li>
		<li><a data-type="ORG"><?php echo $l->t('Organization'); ?></a></li>
		<li><a data-type="NICKNAME"><?php echo $l->t('Nickname'); ?></a></li>
		<li><a data-type="BDAY"><?php echo $l->t('Birthday'); ?></a></li>
		<li><a data-type="TEL"><?php echo $l->t('Phone'); ?></a></li>
		<li><a data-type="EMAIL"><?php echo $l->t('Email'); ?></a></li>
		<li><a data-type="ADR"><?php echo $l->t('Address'); ?></a></li>
	</ul>
	<img  onclick="Contacts.UI.Card.export();" class="svg action" id="contacts_downloadcard" src="<?php echo image_path('', 'actions/download.svg'); ?>" title="<?php echo $l->t('Download contact');?>" />
	<img class="svg action" id="contacts_deletecard" src="<?php echo image_path('', 'actions/delete.svg'); ?>" title="<?php echo $l->t('Delete contact');?>" />
	</div>

	<div class="contactsection">

	<form <?php echo (is_null($card['PHOTO'])?'style="display:none;"':''); ?> id="file_upload_form" action="ajax/uploadphoto.php" method="post" enctype="multipart/form-data" target="file_upload_target">
	<fieldset id="photo" class="formfloat">
		<div id="contacts_details_photo_wrapper" title="<?php echo $l->t('Click or drop to upload picture'); ?> (max <?php echo $_['uploadMaxHumanFilesize']; ?>)">
		<!-- img style="padding: 1em;" id="contacts_details_photo" alt="Profile picture"  src="photo.php?id=<?php echo $_['id']; ?>" / -->
		<progress id="contacts_details_photo_progress" style="display:none;" value="0" max="100">0 %</progress>
		</div>
		<input type="hidden" name="id" value="<?php echo $_['id'] ?>">
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $_['uploadMaxFilesize'] ?>" id="max_upload">
		<input type="hidden" class="max_human_file_size" value="(max <?php echo $_['uploadMaxHumanFilesize']; ?>)">
		<input id="file_upload_start" type="file" accept="image/*" name="imagefile" />
		<iframe name="file_upload_target" id='file_upload_target' src=""></iframe>
	</fieldset>
	</form>
	<form id="contact_identity" method="post" <?php echo ($_['id']==''||!isset($_['id'])?'style="display:none;"':''); ?>>
	<input type="hidden" name="id" value="<?php echo $_['id'] ?>">
	<fieldset class="propertycontainer" data-element="N"><input type="hidden" id="n" class="contacts_property" name="value" value="" /></fieldset>
	<fieldset id="ident" class="formfloat">
	<!-- legend>Name</legend -->
	<dl class="form">
		<!-- dt><label for="n"><?php echo $l->t('Name'); ?></label></dt>
		<dd style="padding-top: 0.8em;vertical-align: text-bottom;"><span id="n" type="text"></span></dd -->
		<dt><label for="fn"><?php echo $l->t('Display name'); ?></label></dt>
		<dd class="propertycontainer" data-element="FN">
		<select id="fn" name="value" required="required" class="contacts_property" title="<?php echo $l->t('Format custom, Short name, Full name, Reverse or Reverse with comma'); ?>" style="width:16em;">
			<option id="short" title="Short name"></option>
			<option id="full" title="Full name"></option>
			<option id="reverse" title="Reverse"></option>
			<option id="reverse_comma" title="Reverse with comma"></option>
		</select><a id="edit_name" class="edit" title="<?php echo $l->t('Edit name details'); ?>"></a>
		</dd>
		<dt style="display:none;" id="org_label" data-element="ORG"><label for="org"><?php echo $l->t('Organization'); ?></label></dt>
		<dd style="display:none;" class="propertycontainer" id="org_value" data-element="ORG"><input id="org"  required="required" name="value[ORG]" type="text" class="contacts_property" style="width:16em;" name="value" value="" placeholder="<?php echo $l->t('Organization'); ?>" /><a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'single');" title="<?php echo $l->t('Delete'); ?>"></a></dd>
		<dt style="display:none;" id="nickname_label" data-element="NICKNAME"><label for="nickname"><?php echo $l->t('Nickname'); ?></label></dt>
		<dd style="display:none;" class="propertycontainer" id="nickname_value" data-element="NICKNAME"><input id="nickname" required="required" name="value[NICKNAME]" type="text" class="contacts_property" style="width:16em;" name="value" value="" placeholder="<?php echo $l->t('Enter nickname'); ?>" /><a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'single');" title="<?php echo $l->t('Delete'); ?>"></a></dd>
		<dt style="display:none;" id="bday_label" data-element="BDAY"><label for="bday"><?php echo $l->t('Birthday'); ?></label></dt>
		<dd style="display:none;" class="propertycontainer" id="bday_value" data-element="BDAY"><input id="bday"  required="required" name="value" type="text" class="contacts_property" value="" placeholder="<?php echo $l->t('dd-mm-yyyy'); ?>" /><a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'single');" title="<?php echo $l->t('Delete'); ?>"></a></dd>
	</dl>
	</fieldset>
	</form>
	</div>

	<!-- div class="delimiter"></div -->
	<form id="contact_communication" method="post">
	<div class="contactsection">
		<!-- email addresses -->
		<div id="emails" <?php echo (count($card['EMAIL'])>0?'':'style="display:none;"'); ?>>
		<fieldset class="contactpart">
		<legend><?php echo $l->t('Email'); ?></legend>
			<ul id="emaillist" class="propertylist">
			<li class="template" style="white-space: nowrap; display: none;" data-element="EMAIL">
				<input type="checkbox" class="contacts_property" name="parameters[TYPE][]" value="PREF" title="<?php echo $l->t('Preferred'); ?>" />
				<input type="email" required="required" class="nonempty contacts_property" style="width:15em;" name="value" value="" x-moz-errormessage="<?php echo $l->t('Please specify a valid email address.'); ?>" placeholder="<?php echo $l->t('Enter email address'); ?>" /><span class="listactions"><a onclick="Contacts.UI.mailTo(this)" class="mail" title="<?php echo $l->t('Mail to address'); ?>"></a>
				<a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'list');" title="<?php echo $l->t('Delete email address'); ?>"></a></span></li>
			<?php
			if(0) { /*foreach($card['EMAIL'] as $email) {*/
			?>
			<li class="propertycontainer" style="white-space: nowrap;" data-checksum="<?php echo $email['checksum'] ?>" data-element="EMAIL">
				<input type="checkbox" class="contacts_property" name="parameters[TYPE][]" value="PREF" title="<?php echo $l->t('Preferred'); ?>" <?php echo (isset($email['parameters']['PREF'])?'checked="checked"':''); ?> />
				<input type="email" required="required" class="nonempty contacts_property" style="width:15em;" name="value" value="<?php echo $email['value'] ?>" placeholder="<?php echo $l->t('Enter email address'); ?>" /><span class="listactions"><a onclick="Contacts.UI.mailTo(this)" class="mail" title="<?php echo $l->t('Mail to address'); ?>"></a>
				<a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'list');" title="<?php echo $l->t('Delete email address'); ?>"></a></span></li>
			<?php } ?>
			</ul><!-- a id="add_email" class="add" title="<?php echo $l->t('Add email address'); ?>"></a -->
		</div> <!-- email addresses-->

		<!-- Phone numbers -->
		<div id="phones" <?php echo (count($card['TEL'])>0?'':'style="display:none;"'); ?>>
		<fieldset class="contactpart">
		<legend><?php echo $l->t('Phone'); ?></legend>
			<ul id="phonelist" class="propertylist">
				<li class="template" style="white-space: nowrap; display: none;" data-element="TEL">
				<input type="checkbox" class="contacts_property" name="parameters[TYPE][]" value="PREF" title="<?php echo $l->t('Preferred'); ?>" /> 
				<input type="text" required="required" class="nonempty contacts_property" style="width:10em; border: 0px;" name="value" value="" placeholder="<?php echo $l->t('Enter phone number'); ?>" />
				<select multiple="multiple" name="parameters[TYPE][]">
					<?php echo html_select_options($_['phone_types'], array()) ?>
				</select>
				<a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'list');" title="<?php echo $l->t('Delete phone number'); ?>"></a></li>
			<?php
			if(0) { /*foreach($card['TEL'] as $phone) {*/
			?>
				<li class="propertycontainer" style="white-space: nowrap;" data-checksum="<?php echo $phone['checksum'] ?>" data-element="TEL">
				<input type="checkbox" class="contacts_property" name="parameters[TYPE][]" value="PREF" title="<?php echo $l->t('Preferred'); ?>" <?php echo (isset($phone['parameters']['PREF'])?'checked="checked"':''); ?> /> 
				<input type="text" required="required" class="nonempty contacts_property" style="width:8em; border: 0px;" name="value" value="<?php echo $phone['value'] ?>" placeholder="<?php echo $l->t('Enter phone number'); ?>" />
				<select class="contacts_property" multiple="multiple" name="parameters[TYPE][]">
					<?php echo html_select_options($_['phone_types'], isset($phone['parameters']['TYPE'])?$phone['parameters']['TYPE']:array()) ?>
				</select>
				<a class="delete" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'list');" title="<?php echo $l->t('Delete phone number'); ?>"></a></li>
			<?php } ?>
			</ul><!-- a id="add_phone" class="add" title="<?php echo $l->t('Add phone number'); ?>"></a -->
		</div> <!-- Phone numbers -->

		<!-- Addresses -->
		<div id="addresses" <?php echo (count($card['ADR'])>0?'':'style="display:none;"'); ?>>
		<fieldset class="contactpart">
		<legend><?php echo $l->t('Address'); ?></legend>
		<div id="addressdisplay">
			<dl class="addresscard template" style="display: none;" data-element="ADR"><dt>
			<input class="adr contacts_property" name="value" type="hidden" value="" />
			<input type="hidden" class="adr_type contacts_property" name="parameters[TYPE][]" value="" />
			<span class="adr_type_label"></span><a class="globe" style="float:right;" onclick="$(this).tipsy('hide');Contacts.UI.searchOSM(this);" title="<?php echo $l->t('View on map'); ?>"></a><a class="edit" style="float:right;" onclick="$(this).tipsy('hide');Contacts.UI.Card.editAddress(this, false);" title="<?php echo $l->t('Edit address details'); ?>"></a><a class="delete" style="float:right;" onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'list');" title="Delete address"></a>
			</dt><dd><ul class="addresslist"></ul></dd></dl>

			<?php if(0) { /*foreach($card['ADR'] as $address) {*/ ?>
			<dl class="addresscard propertycontainer" data-checksum="<?php echo $address['checksum']; ?>" data-element="ADR">
			<dt>
			<input class="adr contacts_property" name="value" type="hidden" value="<?php echo implode(';',$address['value']); ?>" />
				<input type="hidden" class="adr_type contacts_property" name="parameters[TYPE][]" value="<?php echo strtoupper(implode(',',$address['parameters'])); ?>" />
				<span class="adr_type_label">
				<?php 
				if(count($address['parameters']) > 0) {
					//array_walk($address['parameters'], ) Nah, this wont work...
					$translated = array();
					foreach($address['parameters'] as $type) {
						$translated[] = $l->t(ucwords(strtolower($type)));
					}
					echo implode('/', $translated);
				}
				?></span><a class="globe" style="float:right;" onclick="$(this).tipsy('hide');Contacts.UI.searchOSM(this);" title="<?php echo $l->t('View on map'); ?>"></a><a class="edit" style="float:right;" onclick="$(this).tipsy('hide');Contacts.UI.Card.editAddress(this, false);" title="<?php echo $l->t('Edit address details'); ?>"></a><a class="delete" style="float:right;"  onclick="$(this).tipsy('hide');Contacts.UI.Card.deleteProperty(this, 'list');" title="Delete address"></a>
			</dt>
			<dd>
			<ul class="addresslist">
				<?php
				$adr = $address['value'];
				$tmp = ($adr[0]?'<li>'.$adr[0].'</li>':'');
				$tmp .= ($adr[1]?'<li>'.$adr[1].'</li>':'');
				$tmp .= ($adr[2]?'<li>'.$adr[2].'</li>':'');
				$tmp .= ($adr[3]||$adr[5]?'<li>'.$adr[5].' '.$adr[3].'</li>':'');
				$tmp .= ($adr[4]?'<li>'.$adr[4].'</li>':'');
				$tmp .= ($adr[6]?'<li>'.$adr[6].'</li>':'');
				echo $tmp;
				
				?>
			</ul>
			</dd>
			</dl>
			<?php } ?>
		</fieldset>
		</div>
		</div> <!-- Addresses -->
	</div>
	<!-- a id="add_address" onclick="Contacts.UI.Card.editAddress('new', true)" class="add" title="<?php echo $l->t('Add address'); ?>"></a -->
	</div> 
	</form>
</div>
<div class="delimiter"></div>
<pre>
<?php /*print_r($card);*/ ?>
</pre>
	<!-- div class="updatebar"><input type="button" value="Update" /></div -->
<div id="edit_photo_dialog" title="Edit photo">
		<div id="edit_photo_dialog_img"></div>
</div>
<script language="Javascript">
$(document).ready(function(){
	if('<?php echo $id; ?>'!='') {
		$.getJSON(OC.filePath('contacts', 'ajax', 'contactdetails.php'),{'id':'<?php echo $id; ?>'},function(jsondata){
			if(jsondata.status == 'success'){
				Contacts.UI.Card.loadContact(jsondata.data);
			}
			else{
				Contacts.UI.messageBox(t('contacts', 'Error'), jsondata.data.message);
			}
		});
	}
});
</script>
