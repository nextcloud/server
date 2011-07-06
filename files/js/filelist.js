FileList={
	update:function(fileListHtml) {
		$('#fileList').empty().html(fileListHtml);
	},
	addFile:function(name,size,lastModified){
		var html='<tr data-file="'+name+'" data-type="file">';
		html+='<td class="selection"><input type="checkbox" /></td>';
		html+='<td class="filename"><a style="background-image:url(img/file.png)" href="download.php?file='+$('#dir').val()+'/'+name+'">'+name+'</a></td>';
		html+='<td class="filesize">'+size+'</td>';
		html+='<td class="date">'+lastModified+'</td>';
		html+='<td class="fileaction"><a href="" title="+" class="dropArrow"></a></td>';
		html+='</tr>';
		FileList.insertElement(name,'file',$(html));
	},
	addDir:function(name,size,lastModified){
		var html='<tr data-file="'+name+'" data-type="dir">';
		html+='<td class="selection"><input type="checkbox" /></td>';
		html+='<td class="filename"><a style="background-image:url(img/folder.png)" href="index.php?dir='+$('#dir').val()+'/'+name+'"><strong>'+name+'</strong></a></td>';
		html+='<td class="filesize">'+size+'</td>';
		html+='<td class="date">'+lastModified+'</td>';
		html+='<td class="fileaction"><a href="" title="+" class="dropArrow"></a></td>';
		html+='</tr>';
		
		FileList.insertElement(name,'dir',$(html));
	},
	refresh:function(data) {
		result = jQuery.parseJSON(data.responseText);
		if(typeof(result.data.breadcrumb) != 'undefined'){
			updateBreadcrumb(result.data.breadcrumb);
		}
		FileList.update(result.data.files);
		resetFileActionPanel();
	},
	remove:function(name){
		$('tr[data-file="'+name+'"]').remove();
	},
	insertElement:function(name,type,element){
		//find the correct spot to insert the file or folder
		var fileElements=$('tr[data-file][data-type="'+type+'"]');
		var pos;
		if(name.localeCompare($(fileElements[0]).attr('data-file'))<0){
			pos=0;
		}else if(name.localeCompare($(fileElements[fileElements.length-1]).attr('data-file'))>0){
			pos=fileElements.length-1;
		}else{
			for(var pos=1;pos<fileElements.length-1;pos++){
				if(name.localeCompare($(fileElements[pos]).attr('data-file'))>0 && name.localeCompare($(fileElements[pos+1]).attr('data-file'))<0){
					break;
				}
			}
		}
		if(fileElements.length){
			$(fileElements[pos]).after(element);
		}else{
			$('#fileList').append(element);
		}
	}
}
