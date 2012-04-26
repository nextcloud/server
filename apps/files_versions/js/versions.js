$(document).ready(function(){
	
	// Add history button to files/index.php
	FileActions.register('file','History',function(){return OC.imagePath('core','actions/history')},function(filename){
		
		if (scanFiles.scanning){return;}//workaround to prevent additional http request block scanning feedback
		
		var file = $('#dir').val()+'/'+filename;
		
		createVersionsDropdown(filename, file)

		//window.location='../apps/files_versions/history.php?path='+encodeURIComponent($('#dir').val()).replace(/%2F/g, '/')+'/'+encodeURIComponent(filename);

		
	});
	
});

function createVersionsDropdown(filename, files) {
	var historyUrl = '../apps/files_versions/history.php?path='+encodeURIComponent($('#dir').val()).replace(/%2F/g, '/')+'/'+encodeURIComponent(filename);
	//alert( historyUrl );
	var html = '<div id="dropdown" class="drop" data-file="'+files+'">';
	html += '<div id="private">';
	html += '<select data-placeholder="File Version" id="share_with" class="chzen-select">';
	html += '<option value=""></option>';
	html += '</select>';
	html += '<ul id="shared_list"></ul>';
	html += '</div>';
	html += '<div id="public">';
	html += '<input type="button" name="makelink" id="makelink" value="Revert file" />';
	html += '<input type="button" onclick="window.location=\''+historyUrl+'\'" name="makelink" id="makelink" value="More..." />';
	html += '<br />';
	html += '<input id="link" style="display:none; width:90%;" />';
	html += '</div>';
	
	if (filename) {
		$('tr').filterAttr('data-file',filename).addClass('mouseOver');
		$(html).appendTo($('tr').filterAttr('data-file',filename).find('td.filename'));
	} else {
		$(html).appendTo($('thead .share'));
	}
// 			$.getJSON(OC.linkTo('files_sharing', 'ajax/userautocomplete.php'), function(users) {
// 				if (users) {
// 					$.each(users, function(index, row) {
// 						$(row).appendTo('#share_with');
// 					});
// 					$('#share_with').trigger('liszt:updated');
// 				}
// 			});
// 			$.getJSON(OC.linkTo('files_sharing', 'ajax/getitem.php'), { source: files }, function(users) {
// 				if (users) {
// 					$.each(users, function(index, row) {
// 						if (row.uid_shared_with == 'public') {
// 							showPublicLink(row.token, '/'+filename);
// 						} else if (isNaN(index)) {
// 							addUser(row.uid_shared_with, row.permissions, index.substr(0, index.lastIndexOf('-')));
// 						} else {
// 							addUser(row.uid_shared_with, row.permissions, false);
// 						}
// 					});
// 				}
// 			});

	$('#dropdown').show('blind');
	$('#share_with').chosen();
	
}