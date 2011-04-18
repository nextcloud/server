$(document).ready(function() {
	$('#file_action_panel').attr('activeAction', false);
	
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
		$('#file_upload_target').load(uploadFinished);
	});
	
	$('#file_new_dir_submit').click(function() {
		$.ajax({
			url: 'ajax/newfolder.php',
			data: "dir="+$('#dir').val()+"&foldername="+$('#file_new_dir_name').val(),
			complete: function(data){boolOperationFinished(data, false);}
		});
	});
	
	$('.upload').click(function(){
		if($('#file_action_panel').attr('activeAction') != 'upload') {
			$('#file_action_panel').attr('activeAction', 'upload');
			$('#fileSelector').replaceWith('<input type="file" name="file" id="fileSelector">');
			$('#file_action_panel form').slideUp(250);
			$('#file_upload_form').slideDown(250);
		} else {
			$('#file_action_panel').attr('activeAction', 'false');
			$('#file_upload_form').slideUp(250);
		}
		return false;
	});
	
	$('.new-dir').click(function(){
		if($('#file_action_panel').attr('activeAction') != 'new-dir') {
			$('#file_action_panel').attr('activeAction', 'new-dir');
			$('#file_new_dir_name').val('');
			$('#file_action_panel form').slideUp(250);
			$('#file_newfolder_form').slideDown(250);
		} else {
			$('#file_newfolder_form').slideUp(250);
			$('#file_action_panel').attr('activeAction', false);
		}
		return false;
	});
	
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
});

function uploadFinished() {
	result = $('#file_upload_target').contents().text();
	result = eval("(" + result + ");");
	if(result.status == "error") {
		alert('An error occcured, upload failed.\nError code: ' + result.data.error);
	} else {
		dir = $('#dir').val();
		$.ajax({
			url: 'ajax/list.php',
			data: "dir="+dir,
			complete: refreshContents
		});
	}
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
