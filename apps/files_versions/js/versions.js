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
			// Check if drop down is already visible for a different file
			if (($('#dropdown').length > 0)) {
				if (file != $('#dropdown').data('file')) {
					$('#dropdown').hide('blind', function() {
						$('#dropdown').remove();
						$('tr').removeClass('mouseOver');
						createVersionsDropdown(filename, file);
					});
				}
			} else {
				createVersionsDropdown(filename, file);
			}
		});
	}
});

function createVersionsDropdown(filename, files) {
	
	var historyUrl = OC.linkTo('files_versions', 'history.php') + '?path='+encodeURIComponent( $( '#dir' ).val() ).replace( /%2F/g, '/' )+'/'+encodeURIComponent( filename );
	
	var html = '<div id="dropdown" class="drop" data-file="'+files+'">';
	html += '<div id="private">';
	html += '<select data-placeholder="Saved versions" id="found_versions" class="chzen-select" style="width:16em;">';
	html += '<option value=""></option>';
	html += '</select>';
	html += '</div>';
	//html += '<input type="button" value="Revert file" onclick="revertFile()" />';
	html += '<input type="button" value="All versions..." onclick="window.location=\''+historyUrl+'\'" name="makelink" id="makelink" />';
	html += '<input id="link" style="display:none; width:90%;" />';
	
	if (filename) {
		$('tr').filterAttr('data-file',filename).addClass('mouseOver');
		$(html).appendTo($('tr').filterAttr('data-file',filename).find('td.filename'));
	} else {
		$(html).appendTo($('thead .share'));
	}
	
	$.ajax({
		type: 'GET',
		url: OC.filePath('files_versions', 'ajax', 'getVersions.php'),
		dataType: 'json',
		data: { source: files },
		async: false,
		success: function( versions ) {
			
			//alert("helo "+OC.linkTo('files_versions', 'ajax/getVersions.php'));
			
			if (versions) {
				$.each( versions, function(index, row ) {
					addVersion( row );
				});
				$('#found_versions').chosen();
			} else {
				$('#found_versions').hide();
				$('#makelink').hide();
				$('<div style="text-align:center;">No other versions available</div>').appendTo('#dropdown');
			}
			$('#found_versions').change(function(){
				var revision=parseInt($(this).val());
				revertFile(files,revision);
			})
		}
	});
	
	function revertFile(file, revision) {
		
		$.ajax({
			type: 'GET',
			url: OC.linkTo('files_versions', 'ajax/rollbackVersion.php'),
			dataType: 'json',
			data: {file: file, revision: revision},
			async: false,
			success: function(response) {
				if (response.status=='error') {
					OC.dialogs.alert('Failed to revert '+file+' to revision '+formatDate(revision*1000)+'.','Failed to revert');
				} else {
					$('#dropdown').hide('blind', function() {
						$('#dropdown').remove();
						$('tr').removeClass('mouseOver');
						// TODO also update the modified time in the web ui
					});
				}
			}
		});	
	
	}
	
	function addVersion(revision ) {
		name=formatDate(revision*1000);
		var version=$('<option/>');
		version.attr('value',revision);
		version.text(name);
		
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
		
		version.appendTo('#found_versions');
	}

	$('#dropdown').show('blind');
	
	
}
