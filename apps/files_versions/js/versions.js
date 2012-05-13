$(document).ready(function() {
        $('#versions').bind('change', function() {
                var checked = 1;
                if (!this.checked) {
                        checked = 0;
                }
                $.post(OC.filePath('files_versions','ajax','togglesettings.php'), 'versions='+checked);
        });
});

$(document).ready(function(){
	if (typeof FileActions !== 'undefined') {
		// Add history button to files/index.php
		FileActions.register('file','History',function(){return OC.imagePath('core','actions/history')},function(filename){

			if (scanFiles.scanning){return;}//workaround to prevent additional http request block scanning feedback
			
			var file = $('#dir').val()+'/'+filename;

			createVersionsDropdown(filename, file)

		});
	}
});

function createVersionsDropdown(filename, files) {
	
	var historyUrl = OC.linkTo('files_versions', 'history.php?path='+encodeURIComponent( $( '#dir' ).val() ).replace( /%2F/g, '/' )+'/'+encodeURIComponent( filename ) )
	
	var html = '<div id="dropdown" class="drop" data-file="'+files+'">';
	html += '<div id="private">';
	html += '<select data-placeholder="File Version" id="found_versions" class="chzen-select">';
	html += '<option value="">Saved versions</option>';
	html += '</select>';
	html += '</div>';
	//html += '<input type="button" value="Revert file" onclick="revertFile()" />';
	html += '<input type="button" value="Revert file..." onclick="window.location=\''+historyUrl+'\'" name="makelink" id="makelink" />';
	html += '<br />';
	html += '<input id="link" style="display:none; width:90%;" />';
	
	if (filename) {
		$('tr').filterAttr('data-file',filename).addClass('mouseOver');
		$(html).appendTo($('tr').filterAttr('data-file',filename).find('td.filename'));
	} else {
		$(html).appendTo($('thead .share'));
	}
	
	$.ajax({
		type: 'GET',
		url: OC.linkTo('files_versions', 'ajax/getVersions.php'),
		dataType: 'json',
		data: { source: files },
		async: false,
		success: function( versions ) {
			
			//alert("helo "+OC.linkTo('files_versions', 'ajax/getVersions.php'));
			
			if (versions) {
				
				$.each( versions, function(index, row ) {
						
						addVersion( row );
				});
				
			}
			
		}
	});
	
	function revertFile() {
		
		$.ajax({
			type: 'GET',
			url: OC.linkTo('files_versions', 'ajax/rollbackVersion.php'),
			dataType: 'json',
			data: {path: file, revision: 'revision'},
			async: false,
			success: function(versions) {
				if (versions) {
				}
			}
		});	
		
	}
	
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
