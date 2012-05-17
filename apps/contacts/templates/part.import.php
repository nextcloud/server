<div id="contacts_import_dialog" title="<?php echo $l->t("Import a contacts file"); ?>">
	<div id="form_container">
		<input type="hidden" id="filename" value="<?php echo $_['filename'];?>">
		<input type="hidden" id="path" value="<?php echo $_['path'];?>">
		<input type="hidden" id="progressfile" value="<?php echo md5(session_id()) . '.txt';?>">
		<p class="bold" style="text-align:center;"><?php echo $l->t('Please choose the addressbook'); ?></p>
		<select style="width:100%;" id="contacts" name="contacts">
		<?php
		$contacts_options = OC_Contacts_Addressbook::all(OCP\USER::getUser());
		$contacts_options[] = array('id'=>'newaddressbook', 'displayname'=>$l->t('create a new addressbook'));
		echo OCP\html_select_options($contacts_options, $contacts_options[0]['id'], array('value'=>'id', 'label'=>'displayname'));
		?>
		</select>
		<div id="newaddressbookform" style="display: none;">
			<input type="text" style="width: 97%;" placeholder="<?php echo $l->t('Name of new addressbook'); ?>" id="newaddressbook" name="newaddressbook">
		</div>
		<input type="button" value="<?php echo $l->t("Import");?>!" id="startimport">
	</div>
<div id="progressbar_container" style="display: none">
	<p style="text-align:center;"><?php echo $l->t('Importing contacts'); ?></p>
	<div id="progressbar"></div>
		<div id="import_done" style="display: none;">
			<p style="text-align:center;"></p>
			<input type="button" value="<?php echo $l->t('Close'); ?>" id="import_done_button">
		</div>
	</div>
</div>