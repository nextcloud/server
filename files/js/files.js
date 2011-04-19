$(document).ready(function() {
	$('#file_action_panel').attr('activeAction', false);
	$('#file_upload_start').attr('mode', 'menu');
	$('#file_upload_form').attr('uploading', false);
	$('#file_newfolder_name').css('width', '14em');
	$('#file_newfolder_submit').css('width', '3em');
	
    // Sets browser table behaviour :
    $('.browser tr').hover(
        function() {
            $(this).addClass('mouseOver');
        },
        function() {
            $(this).removeClass('mouseOver');
        }
    );

    // Sets logs table behaviour :
    $('.logs tr').hover(
        function() {
            $(this).addClass('mouseOver');
        },
        function() {
            $(this).removeClass('mouseOver');
        }
    );

    // Sets the file-action buttons behaviour :
    $('td.fileaction a').click(function() {
        $(this).parent().append($('#file_menu'));
        $('#file_menu').slideToggle(250);
        return false;
    });

    // Sets the select_all checkbox behaviour :
    $('#select_all').click(function() {

        if($(this).attr('checked'))
            // Check all
            $('td.selection input:checkbox').attr('checked', true);
        else
            // Uncheck all
            $('td.selection input:checkbox').attr('checked', false);
    });
	
	$('td.selection input:checkbox').click(function() {
		if(!$(this).attr('checked')){
			$('#select_all').attr('checked',false);
		}else{
			if($('td.selection input:checkbox:checked').length==$('td.selection input:checkbox').length){
				$('#select_all').attr('checked',true);
			}
		}
	});
	
	// Download current file 
	$('#download_single_file').click(function() {
		filename = $('#file_menu').parents('tr:first').find('.filename:first').children('a:first').text();
		window.location='ajax/download.php?files='+filename+'&dir='+$('#dir').val();
		$('#file_menu').slideToggle(250);
		return false;
	});
	
	// Delete current file 
	$('#delete_single_file').click(function() {
		filename = $('#file_menu').parents('tr:first').find('.filename:first').children('a:first').text();
		$.ajax({
			url: 'ajax/delete.php',
			data: "dir="+$('#dir').val()+"&file="+filename,
			complete: function(data){
				boolOperationFinished(data, true, $('#file_menu').parents('tr:first'));
			}
		});
		return false;
	});
	
	$('#file_upload_start').click(function() {
		if($('#file_upload_start').attr('mode') == 'menu') {
			$('#file_upload_form')[0].reset();
			$('#fileSelector').change(function() {
				//Chromium prepends C:\fakepath....
				bspos = $('#fileSelector').val().lastIndexOf('\\')+1;
				filename = $('#fileSelector').val().substr(bspos);
				
				$('#file_upload_start').val('Upload ' + filename);
				$('#fileSelector').hide();
				$('#file_upload_cancel').slideDown(250);
				$('#file_upload_start').attr('mode', 'action');
			});
			$('#fileSelector').show();	//needed for Chromium compatibility
			//rekonq does not call change-event, when click() is executed by script
			if(navigator.userAgent.indexOf('rekonq') == -1){ 
				$('#fileSelector').click();
			}
		} else if($('#file_upload_start').attr('mode') == 'action') {
			$('#file_upload_cancel').slideUp(250);
			$('#file_upload_form').attr('uploading', true);
			$('#file_upload_target').load(uploadFinished);
		}
	});
	
	$('#file_upload_cancel').click(function() {
		$('#file_upload_form')[0].reset();
		$('#file_upload_start').val('Upload ' + $('.max_human_file_size:first').val());
		$('#file_upload_start').attr('mode', 'menu');
		$('#file_upload_cancel').hide();
// 		$('#file_action_panel').attr('activeAction', 'false');
// 		$('#file_upload_form').hide();
// 		$('p.actions a.upload:first').show();
	});
	
	$('#file_new_dir_submit').click(function() {
		$.ajax({
			url: 'ajax/newfolder.php',
			data: "dir="+$('#dir').val()+"&foldername="+$('#file_new_dir_name').val(),
			complete: function(data){boolOperationFinished(data, false);}
		});
	});
	
	$('#file_newfolder_name').click(function(){
		if($('#file_newfolder_name').val() == 'New Folder'){
			$('#file_newfolder_name').val('');
		}
	});
	
	$('#file_newfolder_name').bind('keyup', adjustNewFolderSize);
	
	$('#file_newfolder_submit').bind('vanish', function() {
		$('#file_newfolder_name').bind('keyup', adjustNewFolderSize);
		unsplitSize($('#file_newfolder_name'),$('#file_newfolder_submit'));
	});
	
	$('#file_newfolder_name').focusout(function(){
		if($('#file_newfolder_name').val() == '') {
			$('#file_newfolder_form')[0].reset();
			$('#file_newfolder_submit').fadeOut(250).trigger('vanish');
		}
	});
	
	$('#file_newfolder_submit').click(function() {
		if($('#file_newfolder_name').val() != '') {
			$.ajax({
				url: 'ajax/newfolder.php',
				data: "dir="+$('#dir').val()+"&foldername="+$('#file_newfolder_name').val(),
				complete: function(data){
					boolOperationFinished(data, false);
					$('#file_newfolder_form')[0].reset();
				}
			});
		}
		$('#file_newfolder_submit').fadeOut(250).trigger('vanish');
	});
	
// 	$('.upload').click(function(){
// 		if($('#file_action_panel').attr('activeAction') != 'upload') {
// 			$('#file_action_panel').attr('activeAction', 'upload');
// 			$('#fileSelector').replaceWith('<input type="file" name="file" id="fileSelector">');
// 			$('#fileSelector').change(function() {
// 				$('#file_upload_start').val('Upload ' + $('#fileSelector').val());
// 				$('p.actions a.upload:first').after($('#file_upload_form'));
// 				$('#file_upload_form').css('display', 'inline');
// 				$('p.actions a.upload:first').hide();
// 				$('#fileSelector').hide();
// 			});
// 			$('#file_action_panel form').slideUp(250);
// // 			$('#file_upload_form').slideDown(250);
// 			$('#fileSelector').click();
// 		} else {
// 			$('#file_action_panel').attr('activeAction', 'false');
// 			$('#file_upload_form').slideUp(250);
// 		}
// 		return false;
// 	});
	
	
	
// 	$('.new-dir').click(function(){
// 		if($('#file_action_panel').attr('activeAction') != 'new-dir') {
// 			$('#file_action_panel').attr('activeAction', 'new-dir');
// 			$('#file_new_dir_name').val('');
// 			$('#file_action_panel form').slideUp(250);
// 			$('#file_newfolder_form').slideDown(250);
// 		} else {
// 			$('#file_newfolder_form').slideUp(250);
// 			$('#file_action_panel').attr('activeAction', false);
// 		}
// 		return false;
// 	});
	
	$('.download').click(function(event) {
		var files='';
		$('td.selection input:checkbox:checked').parent().parent().children('.filename').each(function(i,element){
			files+=';'+$(element).text();
		});
		files=files.substr(1);//remove leading ;
		
		//send the browser to the download location
		var dir=$('#dir').val()||'/';
// 		alert(files);
		window.location='ajax/download.php?files='+files+'&dir='+dir;
		return false;
	});
	
	$('.delete').click(function(event) {
		var files='';
		$('td.selection input:checkbox:checked').parent().parent().children('.filename').each(function(i,element){
			files+=';'+$(element).text();
		});
		files=files.substr(1);//remove leading ;
		
		$.ajax({
			url: 'ajax/delete.php',
			data: "dir="+$('#dir').val()+"&files="+files,
			complete: function(data){
				boolOperationFinished(data, false);
			}
		});
		
		return false;
	});
});

var adjustNewFolderSize = function() {
	if($('#file_newfolder_name').val() != '') {
		splitSize($('#file_newfolder_name'),$('#file_newfolder_submit'));
		$('#file_newfolder_name').unbind('keyup', adjustNewFolderSize);
	};
}

function splitSize(existingEl, appearingEl) {
	nw = parseInt($(existingEl).css('width')) - parseInt($(appearingEl).css('width'));
	$(existingEl).css('width', nw + 'px');
	$(appearingEl).fadeIn(250);
}

function unsplitSize(stayingEl, vanishingEl) {
	nw = parseInt($(stayingEl).css('width')) + parseInt($(vanishingEl).css('width'));
	$(stayingEl).css('width', nw + 'px');
	$(vanishingEl).fadeOut(250);
}

function uploadFinished() {
	result = $('#file_upload_target').contents().text();
	result = eval("(" + result + ");");
	$('#file_upload_target').load(function(){});
	if(result.status == "error") {
		if($('#file_upload_form').attr('uploading') == true) {
			alert('An error occcured, upload failed.\nError code: ' + result.data.error + '\nFilename: ' + result.data.file);
		}
	} else {
		dir = $('#dir').val();
		$.ajax({
			url: 'ajax/list.php',
			data: "dir="+dir,
			complete: function(data) {
				refreshContents(data);
// 				$('#file_action_panel').prepend($('#file_upload_form'));
// 				$('#file_upload_form').css('display', 'block').hide();
// 				$('p.actions a.upload:first').show();
				$('#file_upload_start').val('Upload ' + $('.max_human_file_size:first').val());
				$('#file_upload_start').attr('mode', 'menu');
			}
		});
	}
	$('#file_upload_form').attr('uploading', false);
}

function resetFileActionPanel() {
	$('#file_action_panel form').css({"display":"none"});
	$('#file_action_panel').attr('activeAction', false);
}

function boolOperationFinished(data, single, el) {
	result = eval("("+data.responseText+");");
	if(result.status == 'success'){
		if(single) {
			$('#file_menu').slideToggle(0);
			$('body').append($('#file_menu'));
			$(el).remove();
		} else {
			$.ajax({
				url: 'ajax/list.php',
				data: "dir="+$('#dir').val(),
				complete: refreshContents
			});
		}
	} else {
		alert(result.data.message);
	}
}

function refreshContents(data) {
	result = eval("("+data.responseText+");");
	if(typeof(result.data.breadcrumb) != 'undefined'){
		updateBreadcrumb(result.data.breadcrumb);
	}
	updateFileList(result.data.files);
	$('td.fileaction a').click(function() {
        $(this).parent().append($('#file_menu'));
        $('#file_menu').slideToggle(250);
        return false;
    });
	resetFileActionPanel();
}

function updateBreadcrumb(breadcrumbHtml) {
	$('p.nav').empty().html(breadcrumbHtml);
}

function updateFileList(fileListHtml) {
	$('#fileList').empty().html(fileListHtml);
}
