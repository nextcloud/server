$(document).ready(function(){
	
	// Add history button to files/index.php
	FileActions.register('file','History',function(){return OC.imagePath('core','actions/history')},function(filename){
		
		if (scanFiles.scanning){return;}//workaround to prevent additional http request block scanning feedback
		
		var file = $('#dir').val()+'/'+filename;
		
		createVersionsDropdown(filename, file)

		$.ajax({
			type: 'GET',
			url: OC.linkTo('files_versions', 'ajax/getVersions.php'),
			dataType: 'json',
			data: {source: file},
			async: false,
			success: function(versions) {
				if (versions) {
					
	// 				icon = OC.imagePath('core', 'actions/shared');
	// 				$.each(users, function(index, row) {
	// 					if (row.uid_shared_with == 'public') {
	// 						icon = OC.imagePath('core', 'actions/public');
	// 					}
	// 				});
	// 			} else {
	// 				icon = OC.imagePath('core', 'actions/share');
				}
	 			shared_status[file]= { timestamp: new Date().getTime(), icon: icon };
			}
		});
	
	});
	
});

function createVersionsDropdown(filename, files) {
	var historyUrl = '../apps/files_versions/history.php?path='+encodeURIComponent($('#dir').val()).replace(/%2F/g, '/')+'/'+encodeURIComponent(filename);
	//alert( historyUrl );
	var html = '<div id="dropdown" class="drop" data-file="'+files+'">';
	html += '<div id="private">';
	html += '<select data-placeholder="File Version" id="found_versions" class="chzen-select">';
	html += '<option value="">Select version</option>';
	html += '</select>';
	html += '</div>';
	html += '<input type="button" name="makelink" id="makelink" value="Revert file" />';
	html += '<input type="button" onclick="window.location=\''+historyUrl+'\'" name="makelink" id="makelink" value="More..." />';
	html += '<br />';
	html += '<input id="link" style="display:none; width:90%;" />';
	
	if (filename) {
		$('tr').filterAttr('data-file',filename).addClass('mouseOver');
		$(html).appendTo($('tr').filterAttr('data-file',filename).find('td.filename'));
	} else {
		$(html).appendTo($('thead .share'));
	}
	
// 	$.getJSON(OC.linkTo('files_sharing', 'ajax/userautocomplete.php'), function(users) {
// 		if (users) {
// 			$.each(users, function(index, row) {
// 				$(row).appendTo('#share_with');
// 			});
// 			$('#share_with').trigger('liszt:updated');
// 		}
// 	});
	$.getJSON(OC.linkTo('files_versions', 'ajax/getVersions.php'), { source: files }, function(versions) {
		if (versions) {
			
			$.each( versions, function(index, row ) {
					
					addVersion( row );
			});
			
		}
		
	});
	
	function addVersion( name ) {
		
		var version = '<option>'+name+'</option>';
		
// 		} else {
// 			var checked = ((permissions > 0) ? 'checked="checked"' : 'style="display:none;"');
// 			var style = ((permissions == 0) ? 'style="display:none;"' : '');
// 			var user = '<li data-uid_shared_with="'+uid_shared_with+'">';
// 			user += '<a href="" class="unshare" style="display:none;"><img class="svg" alt="Unshare" src="'+OC.imagePath('core','actions/delete')+'"/></a>';
// 			user += uid_shared_with;
// 			user += '<input type="checkbox" name="permissions" id="'+uid_shared_with+'" class="permissions" '+checked+' />';
// 			user += '<label for="'+uid_shared_with+'" '+style+'>can edit</label>';
// 			user += '</li>';
// 		}
		
		$(version).appendTo('#found_versions');
	}

	$('#dropdown').show('blind');
	$('#share_with').chosen();
	
}