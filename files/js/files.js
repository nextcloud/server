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
            $('.browser input:checkbox').attr('checked', true);
        else
            // Uncheck all
            $('.browser input:checkbox').attr('checked', false);
    });
	
	$('#file_upload_start').click(function() {		
		$('#file_upload_target').load(uploadFinished);
	});
	
	$('#file_new_dir_submit').click(function() {
		$.ajax({
			url: 'ajax/newfolder.php',
			data: "dir="+$('#dir').val()+"&foldername="+$('#file_new_dir_name').val(),
			complete: boolOpFinished
		});
	});
	
	$('.upload').click(function(){
		if($('#file_action_panel').attr('activeAction') != 'upload') {
			$('#file_action_panel').attr('activeAction', 'upload');
			$('#fileSelector').replaceWith('<input type="file" name="file" id="fileSelector">');
			$('#file_action_panel form').css({"display":"none"});
			$('#file_upload_form').css({"display":"block"});
		} else {
			$('#file_action_panel').attr('activeAction', 'false');
			$('#file_upload_form').css({"display":"none"})
		}
		return false;
	});
	
	$('.new-dir').click(function(){
		if($('#file_action_panel').attr('activeAction') != 'new-dir') {
			$('#file_action_panel').attr('activeAction', 'new-dir');
			$('#file_new_dir_name').val('');
			$('#file_action_panel form').css({"display":"none"});
			$('#file_newfolder_form').css({"display":"block"})
		} else {
			$('#file_newfolder_form').css({"display":"none"})
			$('#file_action_panel').attr('activeAction', false);
		}
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

function boolOpFinished(data) {
	result = eval("("+data.responseText+");");
	if(result.status == 'success'){
		$.ajax({
			url: 'ajax/list.php',
			data: "dir="+$('#dir').val(),
			complete: refreshContents
		});
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
	$('#file_upload_button').click();
	resetFileActionPanel();
}

function updateBreadcrumb(breadcrumbHtml) {
	$('p.nav').empty().html(breadcrumbHtml);
}

function updateFileList(fileListHtml) {
	$('#fileList').empty().html(fileListHtml);
}
