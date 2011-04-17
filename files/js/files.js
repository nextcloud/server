$(document).ready(function() {
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
	
	// Shows and hides file upload form
    $('#file_upload_button').toggle(function() {
		$('#file_upload_form').css({"display":"block"});
    }, function() {
		$('#file_upload_form').css({"display":"none"});
	});
	
	$('#file_upload_start').click(function() {		
		$('#file_upload_target').load(uploadFinished);
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

function refreshContents(data) {
	result = eval("("+data.responseText+");");
	if(typeof(result.data.breadcrumb) != 'undefined'){
		updateBreadcrumb(result.data.breadcrumb);
	}
	updateFileList(result.data.files);
	$('#file_upload_button').click();
}

function updateBreadcrumb(breadcrumbHtml) {
	$('p.nav').empty().html(breadcrumbHtml);
}

function updateFileList(fileListHtml) {
	$('#fileList').empty().html(fileListHtml);
}
