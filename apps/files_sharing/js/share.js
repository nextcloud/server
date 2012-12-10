$(document).ready(function() {

	if (typeof OC.Share !== 'undefined' && typeof FileActions !== 'undefined'  && !publicListView) {
		
		FileActions.register('all', 'Share', OC.PERMISSION_READ, OC.imagePath('core', 'actions/share'), function(filename) {
			if ($('#dir').val() == '/') {
				var item = $('#dir').val() + filename;
			} else {
				var item = $('#dir').val() + '/' + filename;
			}
			var tr = $('tr').filterAttr('data-file', filename);
			if ($(tr).data('type') == 'dir') {
				var itemType = 'folder';
			} else {
				var itemType = 'file';
			}
			var possiblePermissions = $(tr).data('permissions');
			var appendTo = $(tr).find('td.filename');
			// Check if drop down is already visible for a different file
			if (OC.Share.droppedDown) {
				if ($(tr).data('id') != $('#dropdown').attr('data-item-source')) {
					OC.Share.hideDropDown(function () {
						$(tr).addClass('mouseOver');
						OC.Share.showDropDown(itemType, $(tr).data('id'), appendTo, true, possiblePermissions);
					});
				} else {
					OC.Share.hideDropDown();
				}
			} else {
				$(tr).addClass('mouseOver');
				OC.Share.showDropDown(itemType, $(tr).data('id'), appendTo, true, possiblePermissions);
			}
		});
		OC.Share.loadIcons('file');
	}

    $('#emailPrivateLink').live('submit', function(event) {
        event.preventDefault();
        var link = $('#linkText').val();
        var itemType = $('#dropdown').data('item-type');
        var itemSource = $('#dropdown').data('item-source');

        var file = $('tr').filterAttr('data-id', String(itemSource)).data('file');
        var email = $('#email').val();
        if (email != '') {
            $.post(OC.filePath('files_sharing', 'ajax', 'email.php'), { toaddress: email, link: link, type: itemType, file: file }, function(result) {
                if (result && result.status == 'success') {
                    $('#email').css('font-weight', 'bold');
                    $('#email').animate({ fontWeight: 'normal' }, 2000, function() {
                        $(this).val('');
                    }).val('Email sent');
                } else {
                    OC.dialogs.alert(result.data.message, 'Error while sharing');
                }
            });
        }
    });


});