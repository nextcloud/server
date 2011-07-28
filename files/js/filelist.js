FileList={
	update:function(fileListHtml) {
		$('#fileList').empty().html(fileListHtml);
	},
	addFile:function(name,size,lastModified,loading){
		var img=(loading)?'img/loading.gif':'img/file.png';
		var html='<tr data-file="'+name+'" data-type="file">';
		html+='<td class="filename"><input type="checkbox" /><a class="name" style="background-image:url('+img+')" href="download.php?file='+$('#dir').val()+'/'+name+'">'+name+'</a></td>';
		html+='<td class="filesize">'+size+'</td>';
		html+='<td class="date">'+lastModified+'</td>';
		html+='</tr>';
		FileList.insertElement(name,'file',$(html));
		if(loading){
			$('tr[data-file="'+name+'"]').data('loading',true);
		}else{
			$('tr[data-file="'+name+'"] td.filename').draggable(dragOptions);
		}
	},
	addDir:function(name,size,lastModified){
		var html='<tr data-file="'+name+'" data-type="dir">';
		html+='<td class="filename"><input type="checkbox" /><a class="name" style="background-image:url(img/folder.png)" href="index.php?dir='+$('#dir').val()+'/'+name+'"><strong>'+name+'</strong></a></td>';
		html+='<td class="filesize">'+size+'</td>';
		html+='<td class="date">'+lastModified+'</td>';
		html+='</tr>';
		
		FileList.insertElement(name,'dir',$(html));
		$('tr[data-file="'+name+'"] td.filename').draggable(dragOptions);
		$('tr[data-file="'+name+'"] td.filename').droppable(folderDropOptions);
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
		$('tr[data-file="'+name+'"] td.filename').draggable('destroy');
		$('tr[data-file="'+name+'"]').remove();
	},
	insertElement:function(name,type,element){
		//find the correct spot to insert the file or folder
		var fileElements=$('tr[data-file][data-type="'+type+'"]');
		var pos;
		if(name.localeCompare($(fileElements[0]).attr('data-file'))<0){
			pos=-1;
		}else if(name.localeCompare($(fileElements[fileElements.length-1]).attr('data-file'))>0){
			pos=fileElements.length-1;
		}else{
			for(var pos=0;pos<fileElements.length-1;pos++){
				if(name.localeCompare($(fileElements[pos]).attr('data-file'))>0 && name.localeCompare($(fileElements[pos+1]).attr('data-file'))<0){
					break;
				}
			}
		}
		if(fileElements.length){
			if(pos==-1){
				$(fileElements[0]).before(element);
			}else{
				$(fileElements[pos]).after(element);
			}
		}else if(type=='dir' && $('tr[data-file]').length>0){
			$('tr[data-file]').first().before(element);
		}else{
			$('#fileList').append(element);
		}
	},
	loadingDone:function(name){
		$('tr[data-file="'+name+'"]').data('loading',false);
		$('tr[data-file="'+name+'"] td.filename a').attr('style','background-image:url(img/file.png');
		$('tr[data-file="'+name+'"] td.filename').draggable(dragOptions);
	},
	isLoading:function(name){
		return $('tr[data-file="'+name+'"]').data('loading');
	},
	rename:function(name){
		var tr=$('tr[data-file="'+name+'"]');
		var td=tr.children('td.filename');
		var input=$('<input value='+name+' class="filename"></input>');
		var button=$('<input type="sumit" value="Ok"></input>');
		var form=$('<form action="#"></form>')
		form.append(input);
		form.append(button);
		td.children('a.name').text('');
		td.children('a.name').append(form)
		input.focus();
		td.children('a.name').append(button);
		form.submit(function(event){
			var newname=input.val();
			event.stopPropagation();
			event.preventDefault();
			tr.attr('data-file',newname);
			td.children('a.name').empty();
			td.children('a.name').text(newname);
			$.ajax({
				url: 'ajax/rename.php',
				data: "dir="+$('#dir').val()+"&newname="+encodeURIComponent(newname)+"&file="+encodeURIComponent(name)
			});
		});
		form.click(function(event){
			event.stopPropagation();
			event.preventDefault();
		});
		input.blur(function(){
			td.children('a.name').empty();
			td.children('a.name').text(name);
		});
	}
}
