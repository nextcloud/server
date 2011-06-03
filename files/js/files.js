$(document).ready(function() {
	$('#file_action_panel').attr('activeAction', false);
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

	$('#file_upload_start').change(function(){
		var filename=$(this).val();
		filename=filename.replace(/^.*[\/\\]/g, '');
		$('#file_upload_filename').val(filename);
		$('#file_upload_submit').show();
	})
	
	$('#file_upload_submit').click(function(){
		$('#file_upload_form').submit();
		var name=$('#file_upload_filename').val();
		if($('#file_upload_start')[0].files[0] && $('#file_upload_start')[0].files[0].size>0){
			var size=humanFileSize($('#file_upload_start')[0].files[0].size);
		}else{
			var size='Pending';
		}
		var date=new Date();
		var monthNames = [ "January", "February", "March", "April", "May", "June",
		"July", "August", "September", "October", "November", "December" ];
		var uploadTime=monthNames[date.getMonth()]+' '+date.getDate()+', '+date.getFullYear()+', '+((date.getHours()<10)?'0':'')+date.getHours()+':'+date.getMinutes();
		var html='<tr>';
		html+='<td class="selection"><input type="checkbox" /></td>';
		html+='<td class="filename"><a style="background-image:url(img/file.png)" href="download.php?file='+$('#dir').val()+'/'+name+'">'+name+'</a></td>';
		html+='<td class="filesize">'+size+'</td>';
		html+='<td class="date">'+uploadTime+'</td>';
		html+='<td class="fileaction"><a href="" title="+" class="dropArrow"></a></td>';
		html+='</tr>';
		$('#fileList').append($(html));
		$('#file_upload_filename').val($('#file_upload_filename').data('upload_text'));
	});
	//save the original upload button text
	$('#file_upload_filename').data('upload_text',$('#file_upload_filename').val());
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

function humanFileSize(bytes){
	if( bytes < 1024 ){
		return bytes+' B';
	}
	bytes = Math.round(bytes / 1024, 1 );
	if( bytes < 1024 ){
		return bytes+' kB';
	}
	bytes = Math.round( bytes / 1024, 1 );
	if( bytes < 1024 ){
		return bytes+' MB';
	}
	
	// Wow, heavy duty for owncloud
	bytes = Math.round( bytes / 1024, 1 );
	return bytes+' GB';
}