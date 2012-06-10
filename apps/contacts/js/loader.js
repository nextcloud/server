/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
Contacts_Import={
	importdialog: function(filename){
		var path = $('#dir').val();
		$('body').append('<div id="contacts_import"></div>');
		$('#contacts_import').load(OC.filePath('contacts', 'ajax', 'importdialog.php'), {filename:filename, path:path},	function(){Contacts_Import.initdialog(filename);});
	},
	initdialog: function(filename){
		$('#contacts_import_dialog').dialog({
			width : 500,
			close : function() {
				$(this).dialog('destroy').remove();
				$('#contacts_import').remove();
			}
		});
		$('#import_done_button').click(function(){
			$('#contacts_import_dialog').dialog('destroy').remove();
			$('#contacts_import').remove();
		});
		$('#progressbar').progressbar({value: 0});
		$('#startimport').click(function(){
			var filename = $('#filename').val();
			var path = $('#path').val();
			var method = 'old';
			var addressbookid = $('#contacts option:selected').val();
			if($('#contacts option:selected').val() == 'newaddressbook'){
				var method = 'new';
				var addressbookname = $('#newaddressbook').val();
				var addressbookname = $.trim(addressbookname);
				if(addressbookname == ''){
					$('#newaddressbook').css('background-color', '#FF2626');
					$('#newaddressbook').focus(function(){
						$('#newaddressbook').css('background-color', '#F8F8F8');
					});
					return false;
				}
			}
			$('#newaddressbook').attr('readonly', 'readonly');
			$('#contacts').attr('disabled', 'disabled');
			var progressfile = $('#progressfile').val();
			$.post(OC.filePath('contacts', '', 'import.php'), {method: String (method), addressbookname: String (addressbookname), path: String (path), file: String (filename), id: String (addressbookid)}, function(jsondata){
				if(jsondata.status == 'success'){
					$('#progressbar').progressbar('option', 'value', 100);
					$('#import_done').find('p').html(t('contacts', 'Result: ') + jsondata.data.imported + t('contacts', ' imported, ') + jsondata.data.failed + t('contacts', ' failed.'));
				} else {
					$('#import_done').find('p').html(jsondata.message);
				}
				$('#import_done').show().find('p').addClass('bold');
				$('#progressbar').fadeOut('slow');
			});
			$('#form_container').css('display', 'none');
			$('#progressbar_container').css('display', 'block');
			window.setTimeout('Contacts_Import.getimportstatus(\'' + progressfile + '\')', 500);
		});
		$('#contacts').change(function(){
			if($('#contacts option:selected').val() == 'newaddressbook'){
				$('#newaddressbookform').slideDown('slow');
			}else{
				$('#newaddressbookform').slideUp('slow');
			}
		});
	},
	getimportstatus: function(progressfile){
		$.get(OC.filePath('contacts', 'import_tmp', progressfile), function(percent){
			$('#progressbar').progressbar('option', 'value', parseInt(percent));
			if(percent < 100){
				window.setTimeout('Contacts_Import.getimportstatus(\'' + progressfile + '\')', 500);
			}else{
				$('#import_done').css('display', 'block');
			}
		});
	}
}
$(document).ready(function(){
	if(typeof FileActions !== 'undefined'){
		FileActions.register('text/vcard','importaddressbook', '', Contacts_Import.importdialog); 
		FileActions.setDefault('text/vcard','importaddressbook');
		FileActions.register('text/x-vcard','importaddressbook', '', Contacts_Import.importdialog); 
		FileActions.setDefault('text/x-vcard','importaddressbook');
	};
});