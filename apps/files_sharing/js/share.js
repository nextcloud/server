$(document).ready(function() {

	var disableSharing = $('#disableSharing').data('status'),
		sharesLoaded = false;

	if (typeof OC.Share !== 'undefined' && typeof FileActions !== 'undefined'  && !disableSharing) {
		$('#fileList').on('fileActionsReady',function(){
			if (!sharesLoaded){
				OC.Share.loadIcons('file');
				// assume that we got all shares, so switching directories
				// will not invalidate that list
				sharesLoaded = true;
			}
			else{
				OC.Share.updateIcons('file');
			}
		});

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
						OC.Share.showDropDown(itemType, $(tr).data('id'), appendTo, true, possiblePermissions, filename);
					});
				} else {
					OC.Share.hideDropDown();
				}
			} else {
				$(tr).addClass('mouseOver');
				OC.Share.showDropDown(itemType, $(tr).data('id'), appendTo, true, possiblePermissions, filename);
			}
		});
	}
});
