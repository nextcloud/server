var FileList={
	useUndo:true,
	update:function(fileListHtml) {
		$('#fileList').empty().html(fileListHtml);
	},
	createRow:function(type, name, iconurl, linktarget, size, lastModified, permissions){
		var td, simpleSize, basename, extension;
		//containing tr
		var tr = $('<tr></tr>').attr({
			"data-type": type,
			"data-size": size,
			"data-file": name,
			"data-permissions": permissions
		});
		// filename td
		td = $('<td></td>').attr({
			"class": "filename",
			"style": 'background-image:url('+iconurl+')'
		});
		td.append('<input type="checkbox" />');
		var link_elem = $('<a></a>').attr({
			"class": "name",
			"href": linktarget
		});
		//split extension from filename for non dirs
		if (type != 'dir' && name.indexOf('.')!=-1) {
			basename=name.substr(0,name.lastIndexOf('.'));
			extension=name.substr(name.lastIndexOf('.'));
		} else {
			basename=name;
			extension=false;
		}
		var name_span=$('<span></span>').addClass('nametext').text(basename);
		link_elem.append(name_span);
		if(extension){
			name_span.append($('<span></span>').addClass('extension').text(extension));
		}
		//dirs can show the number of uploaded files
		if (type == 'dir') {
			link_elem.append($('<span></span>').attr({
				'class': 'uploadtext',
				'currentUploads': 0
			}));
		}
		td.append(link_elem);
		tr.append(td);

		//size column
		if(size!=t('files', 'Pending')){
			simpleSize = humanFileSize(size);
		}else{
			simpleSize=t('files', 'Pending');
		}
		var sizeColor = Math.round(160-Math.pow((size/(1024*1024)),2));
		var lastModifiedTime = Math.round(lastModified.getTime() / 1000);
		td = $('<td></td>').attr({
			"class": "filesize",
			"style": 'color:rgb('+sizeColor+','+sizeColor+','+sizeColor+')'
		}).text(simpleSize);
		tr.append(td);

		// date column
		var modifiedColor = Math.round((Math.round((new Date()).getTime() / 1000)-lastModifiedTime)/60/60/24*5);
		td = $('<td></td>').attr({ "class": "date" });
		td.append($('<span></span>').attr({
			"class": "modified",
			"title": formatDate(lastModified),
			"style": 'color:rgb('+modifiedColor+','+modifiedColor+','+modifiedColor+')'
		}).text( relative_modified_date(lastModified.getTime() / 1000) ));
		tr.append(td);
		return tr;
	},
	addFile:function(name,size,lastModified,loading,hidden,param){
		var imgurl;

		if (!param) {
			param = {};
		}

		var download_url = null;
		if (!param.download_url) {
			download_url = OC.Router.generate('download', { file: $('#dir').val()+'/'+name });
		} else {
			download_url = param.download_url;
		}

		if (loading) {
			imgurl = OC.imagePath('core', 'loading.gif');
		} else {
			imgurl = OC.imagePath('core', 'filetypes/file.png');
		}
		var tr = this.createRow(
			'file',
			name,
			imgurl,
			download_url,
			size,
			lastModified,
			$('#permissions').val()
		);

		FileList.insertElement(name, 'file', tr);
		if(loading){
			tr.data('loading',true);
		}else{
			tr.find('td.filename').draggable(dragOptions);
		}
		if (hidden) {
			tr.hide();
		}
		FileActions.display(tr.find('td.filename'));
		return tr;
	},
	addDir:function(name,size,lastModified,hidden){

		var tr = this.createRow(
			'dir',
			name,
			OC.imagePath('core', 'filetypes/folder.png'),
			OC.linkTo('files', 'index.php')+"?dir="+ encodeURIComponent($('#dir').val()+'/'+name).replace(/%2F/g, '/'),
			size,
			lastModified,
			$('#permissions').val()
		);

		FileList.insertElement(name,'dir',tr);
		var td = tr.find('td.filename');
		td.draggable(dragOptions);
		td.droppable(folderDropOptions);
		if (hidden) {
			tr.hide();
		}
		FileActions.display(tr.find('td.filename'));
		return tr;
	},
	refresh:function(data) {
		var result = jQuery.parseJSON(data.responseText);
		if(typeof(result.data.breadcrumb) != 'undefined'){
			updateBreadcrumb(result.data.breadcrumb);
		}
		FileList.update(result.data.files);
		resetFileActionPanel();
	},
	remove:function(name){
		$('tr').filterAttr('data-file',name).find('td.filename').draggable('destroy');
		$('tr').filterAttr('data-file',name).remove();
		if($('tr[data-file]').length==0){
			$('#emptyfolder').show();
		}
	},
	insertElement:function(name,type,element){
		//find the correct spot to insert the file or folder
		var pos, fileElements=$('tr[data-file][data-type="'+type+'"]:visible');
		if(name.localeCompare($(fileElements[0]).attr('data-file'))<0){
			pos=-1;
		}else if(name.localeCompare($(fileElements[fileElements.length-1]).attr('data-file'))>0){
			pos=fileElements.length-1;
		}else{
			for(pos=0;pos<fileElements.length-1;pos++){
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
		} else if(type=='file' && $('tr[data-file]').length>0) {
			$('tr[data-file]').last().before(element);
		}else{
			$('#fileList').append(element);
		}
		$('#emptyfolder').hide();
	},
	loadingDone:function(name, id){
		var mime, tr=$('tr').filterAttr('data-file',name);
		tr.data('loading',false);
		mime=tr.data('mime');
		tr.attr('data-mime',mime);
		if (id != null) {
			tr.attr('data-id', id);
		}
		getMimeIcon(mime,function(path){
			tr.find('td.filename').attr('style','background-image:url('+path+')');
		});
		tr.find('td.filename').draggable(dragOptions);
	},
	isLoading:function(name){
		return $('tr').filterAttr('data-file',name).data('loading');
	},
	rename:function(name){
		var tr, td, input, form;
		tr=$('tr').filterAttr('data-file',name);
		tr.data('renaming',true);
		td=tr.children('td.filename');
		input=$('<input class="filename"/>').val(name);
		form=$('<form></form>');
		form.append(input);
		td.children('a.name').hide();
		td.append(form);
		input.focus();
		//preselect input
		var len = input.val().lastIndexOf('.');
		if (len === -1) {
			len = input.val().length;
		}
		input.selectRange(0,len);

		form.submit(function(event){
			event.stopPropagation();
			event.preventDefault();
			var newname=input.val();
			if (!Files.isFileNameValid(newname)) {
				return false;
			} else if (newname != name) {
				if (FileList.checkName(name, newname, false)) {
					newname = name;
				} else {
					// save background image, because it's replaced by a spinner while async request
					var oldBackgroundImage = td.css('background-image');
					// mark as loading
					td.css('background-image', 'url('+ OC.imagePath('core', 'loading.gif') + ')');
					$.ajax({
						url: OC.filePath('files','ajax','rename.php'),
						data: {
							dir : $('#dir').val(),
							newname: newname,
							file: name
						},
						success: function(result) {
							if (!result || result.status === 'error') {
								OC.Notification.show(result.data.message);
								newname = name;
								// revert changes
								tr.attr('data-file', newname);
								var path = td.children('a.name').attr('href');
								td.children('a.name').attr('href', path.replace(encodeURIComponent(name), encodeURIComponent(newname)));
								if (newname.indexOf('.') > 0 && tr.data('type') !== 'dir') {
									var basename=newname.substr(0,newname.lastIndexOf('.'));
								} else {
									var basename=newname;
								}
								td.find('a.name span.nametext').text(basename);
								if (newname.indexOf('.') > 0 && tr.data('type') !== 'dir') {
									if (td.find('a.name span.extension').length === 0 ) {
										td.find('a.name span.nametext').append('<span class="extension"></span>');
									}
									td.find('a.name span.extension').text(newname.substr(newname.lastIndexOf('.')));
								}
								tr.find('.fileactions').effect('highlight', {}, 5000);
								tr.effect('highlight', {}, 5000);
							}
							// remove loading mark and recover old image
							td.css('background-image', oldBackgroundImage);
						}
					});
				}
			}
			tr.data('renaming',false);
			tr.attr('data-file', newname);
			var path = td.children('a.name').attr('href');
			td.children('a.name').attr('href', path.replace(encodeURIComponent(name), encodeURIComponent(newname)));
			if (newname.indexOf('.') > 0 && tr.data('type') != 'dir') {
				var basename=newname.substr(0,newname.lastIndexOf('.'));
			} else {
				var basename=newname;
			}
			td.find('a.name span.nametext').text(basename);
			if (newname.indexOf('.') > 0 && tr.data('type') != 'dir') {
				if (td.find('a.name span.extension').length == 0 ) {
					td.find('a.name span.nametext').append('<span class="extension"></span>');
				}
				td.find('a.name span.extension').text(newname.substr(newname.lastIndexOf('.')));
			}
			form.remove();
			td.children('a.name').show();
			return false;
		});
		input.keyup(function(event){
			if (event.keyCode == 27) {
				tr.data('renaming',false);
				form.remove();
				td.children('a.name').show();
			}
		});
		input.click(function(event){
			event.stopPropagation();
			event.preventDefault();
		});
		input.blur(function(){
			form.trigger('submit');
		});
	},
	checkName:function(oldName, newName, isNewFile) {
		if (isNewFile || $('tr').filterAttr('data-file', newName).length > 0) {
			var html;
			if(isNewFile){
				html = t('files', '{new_name} already exists', {new_name: escapeHTML(newName)})+'<span class="replace">'+t('files', 'replace')+'</span><span class="suggest">'+t('files', 'suggest name')+'</span>&nbsp;<span class="cancel">'+t('files', 'cancel')+'</span>';
			}else{
				html = t('files', '{new_name} already exists', {new_name: escapeHTML(newName)})+'<span class="replace">'+t('files', 'replace')+'</span><span class="cancel">'+t('files', 'cancel')+'</span>';
			}
			html = $('<span>' + html + '</span>');
			html.attr('data-oldName', oldName);
			html.attr('data-newName', newName);
			html.attr('data-isNewFile', isNewFile);
            OC.Notification.showHtml(html);
			return true;
		} else {
			return false;
		}
	},
	replace:function(oldName, newName, isNewFile) {
		// Finish any existing actions
		$('tr').filterAttr('data-file', oldName).hide();
		$('tr').filterAttr('data-file', newName).hide();
		var tr = $('tr').filterAttr('data-file', oldName).clone();
		tr.attr('data-replace', 'true');
		tr.attr('data-file', newName);
		var td = tr.children('td.filename');
		td.children('a.name .span').text(newName);
		var path = td.children('a.name').attr('href');
		td.children('a.name').attr('href', path.replace(encodeURIComponent(oldName), encodeURIComponent(newName)));
		if (newName.indexOf('.') > 0) {
			var basename = newName.substr(0, newName.lastIndexOf('.'));
		} else {
			var basename = newName;
		}
		td.children('a.name').empty();
		var span = $('<span class="nametext"></span>');
		span.text(basename);
		td.children('a.name').append(span);
		if (newName.indexOf('.') > 0) {
			span.append($('<span class="extension">'+newName.substr(newName.lastIndexOf('.'))+'</span>'));
		}
		FileList.insertElement(newName, tr.data('type'), tr);
		tr.show();
		FileList.replaceCanceled = false;
		FileList.replaceOldName = oldName;
		FileList.replaceNewName = newName;
		FileList.replaceIsNewFile = isNewFile;
		FileList.lastAction = function() {
			FileList.finishReplace();
		};
		if (!isNewFile) {
            OC.Notification.showHtml(t('files', 'replaced {new_name} with {old_name}', {new_name: newName}, {old_name: oldName})+'<span class="undo">'+t('files', 'undo')+'</span>');
		}
	},
	finishReplace:function() {
		if (!FileList.replaceCanceled && FileList.replaceOldName && FileList.replaceNewName) {
			$.ajax({url: OC.filePath('files', 'ajax', 'rename.php'), async: false, data: { dir: $('#dir').val(), newname: FileList.replaceNewName, file: FileList.replaceOldName }, success: function(result) {
				if (result && result.status == 'success') {
					$('tr').filterAttr('data-replace', 'true').removeAttr('data-replace');
				} else {
					OC.dialogs.alert(result.data.message, 'Error moving file');
				}
				FileList.replaceCanceled = true;
				FileList.replaceOldName = null;
				FileList.replaceNewName = null;
				FileList.lastAction = null;
			}});
		}
	},
	do_delete:function(files){
		if(files.substr){
			files=[files];
		}
		for (var i=0; i<files.length; i++) {
			var deleteAction = $('tr').filterAttr('data-file',files[i]).children("td.date").children(".action.delete");
			var oldHTML = deleteAction.html();
			var newHTML = '<img class="move2trash" data-action="Delete" title="'+t('files', 'perform delete operation')+'" src="'+ OC.imagePath('core', 'loading.gif') +'"></a>';
			deleteAction.html(newHTML);
		}
		// Finish any existing actions
		if (FileList.lastAction) {
			FileList.lastAction();
		}

		var fileNames = JSON.stringify(files);
		$.post(OC.filePath('files', 'ajax', 'delete.php'),
				{dir:$('#dir').val(),files:fileNames},
				function(result){
					if (result.status == 'success') {
						$.each(files,function(index,file){
							var files = $('tr').filterAttr('data-file',file);
							files.remove();
							files.find('input[type="checkbox"]').removeAttr('checked');
							files.removeClass('selected');
						});
						procesSelection();
					} else {
						$.each(files,function(index,file) {
							var deleteAction = $('tr').filterAttr('data-file',file).children("td.date").children(".move2trash");
							deleteAction.html(oldHTML);
						});
					}
				});
	}
};

$(document).ready(function(){

	// handle upload events
	var file_upload_start = $('#file_upload_start');
	file_upload_start.on('fileuploaddrop', function(e, data) {
		// only handle drop to dir if fileList exists
		if ($('#fileList').length > 0) {
			var dropTarget = $(e.originalEvent.target).closest('tr');
			if(dropTarget && dropTarget.data('type') === 'dir') { // drag&drop upload to folder
				var dirName = dropTarget.data('file');
				// update folder in form
				data.formData = function(form) {
					var formArray = form.serializeArray();
					// array index 0 contains the max files size
					// array index 1 contains the request token
					// array index 2 contains the directory
					var parentDir = formArray[2]['value'];
					if (parentDir === '/') {
						formArray[2]['value'] += dirName;
					} else {
						formArray[2]['value'] += '/'+dirName;
					}
					return formArray;
				}
			}
		}
	});
	file_upload_start.on('fileuploadadd', function(e, data) {
		// only add to fileList if it exists
		if ($('#fileList').length > 0) {

			if(FileList.deleteFiles && FileList.deleteFiles.indexOf(data.files[0].name)!=-1){//finish delete if we are uploading a deleted file
				FileList.finishDelete(null, true); //delete file before continuing
			}

			// add ui visualization to existing folder or as new stand-alone file?
			var dropTarget = $(e.originalEvent.target).closest('tr');
			if(dropTarget && dropTarget.data('type') === 'dir') {
				// add to existing folder
				var dirName = dropTarget.data('file');

				// set dir context
				data.context = $('tr').filterAttr('data-type', 'dir').filterAttr('data-file', dirName);

				// update upload counter ui
				var uploadtext = data.context.find('.uploadtext');
				var currentUploads = parseInt(uploadtext.attr('currentUploads'));
				currentUploads += 1;
				uploadtext.attr('currentUploads', currentUploads);
				if(currentUploads === 1) {
					var img = OC.imagePath('core', 'loading.gif');
					data.context.find('td.filename').attr('style','background-image:url('+img+')');
					uploadtext.text(t('files', '1 file uploading'));
					uploadtext.show();
				} else {
					uploadtext.text(currentUploads + ' ' + t('files', 'files uploading'));
				}
			} else {
				// add as stand-alone row to filelist
				var uniqueName = getUniqueName(data.files[0].name);
				var size=t('files','Pending');
				if(data.files[0].size>=0){
					size=data.files[0].size;
				}
				var date=new Date();
				var param = {};
				if ($('#publicUploadRequestToken').length) {
					param.download_url = document.location.href + '&download&path=/' + $('#dir').val() + '/' + uniqueName;
				}
				// create new file context
				data.context = FileList.addFile(uniqueName,size,date,true,false,param);

			}
		}
	});
	file_upload_start.on('fileuploaddone', function(e, data) {
		// only update the fileList if it exists
		if ($('#fileList').length > 0) {
			var response;
			if (typeof data.result === 'string') {
				response = data.result;
			} else {
				// fetch response from iframe
				response = data.result[0].body.innerText;
			}
			var result=$.parseJSON(response);

			if(typeof result[0] !== 'undefined' && result[0].status === 'success') {
				var file = result[0];

				if (data.context.data('type') === 'file') {
					// update file data
					data.context.attr('data-mime',file.mime).attr('data-id',file.id);
					var size = data.context.data('size');
					if(size!=file.size){
						data.context.attr('data-size', file.size);
						data.context.find('td.filesize').text(humanFileSize(file.size));
					}
					if (FileList.loadingDone) {
						FileList.loadingDone(file.name, file.id);
					}
				} else {
					// update upload counter ui
					var uploadtext = data.context.find('.uploadtext');
					var currentUploads = parseInt(uploadtext.attr('currentUploads'));
					currentUploads -= 1;
					uploadtext.attr('currentUploads', currentUploads);
					if(currentUploads === 0) {
						var img = OC.imagePath('core', 'filetypes/folder.png');
						data.context.find('td.filename').attr('style','background-image:url('+img+')');
						uploadtext.text('');
						uploadtext.hide();
					} else {
						uploadtext.text(currentUploads + ' ' + t('files', 'files uploading'));
					}

					// update folder size
					var size =  parseInt(data.context.data('size'));
					size +=  parseInt(file.size)	;
					data.context.attr('data-size', size);
					data.context.find('td.filesize').text(humanFileSize(size));

				}
			}
		}
	});
	file_upload_start.on('fileuploadfail', function(e, data) {
		// only update the fileList if it exists
		// cleanup files, error notification has been shown by fileupload code
		var tr = data.context;
		if (typeof tr === 'undefined') {
			tr = $('tr').filterAttr('data-file', data.files[0].name);
		}
		if (tr.attr('data-type') === 'dir') {
			//cleanup uploading to a dir
			var uploadtext = tr.find('.uploadtext');
			var img = OC.imagePath('core', 'filetypes/folder.png');
			tr.find('td.filename').attr('style','background-image:url('+img+')');
			uploadtext.text('');
			uploadtext.hide(); //TODO really hide already
		} else {
			//remove file
			tr.fadeOut();
			tr.remove();
		}
	});

	$('#notification').hide();
	$('#notification').on('click', '.undo', function(){
		if (FileList.deleteFiles) {
			$.each(FileList.deleteFiles,function(index,file){
				$('tr').filterAttr('data-file',file).show();
			});
			FileList.deleteCanceled=true;
			FileList.deleteFiles=null;
		} else if (FileList.replaceOldName && FileList.replaceNewName) {
			if (FileList.replaceIsNewFile) {
				// Delete the new uploaded file
				FileList.deleteCanceled = false;
				FileList.deleteFiles = [FileList.replaceOldName];
			} else {
				$('tr').filterAttr('data-file', FileList.replaceOldName).show();
			}
			$('tr').filterAttr('data-replace', 'true').remove();
			$('tr').filterAttr('data-file', FileList.replaceNewName).show();
			FileList.replaceCanceled = true;
			FileList.replaceOldName = null;
			FileList.replaceNewName = null;
			FileList.replaceIsNewFile = null;
		}
		FileList.lastAction = null;
        OC.Notification.hide();
	});
	$('#notification:first-child').on('click', '.replace', function() {
        OC.Notification.hide(function() {
            FileList.replace($('#notification > span').attr('data-oldName'), $('#notification > span').attr('data-newName'), $('#notification > span').attr('data-isNewFile'));
        });
	});
	$('#notification:first-child').on('click', '.suggest', function() {
		$('tr').filterAttr('data-file', $('#notification > span').attr('data-oldName')).show();
        OC.Notification.hide();
	});
	$('#notification:first-child').on('click', '.cancel', function() {
		if ($('#notification > span').attr('data-isNewFile')) {
			FileList.deleteCanceled = false;
			FileList.deleteFiles = [$('#notification > span').attr('data-oldName')];
		}
	});
	FileList.useUndo=(window.onbeforeunload)?true:false;
	$(window).bind('beforeunload', function (){
		if (FileList.lastAction) {
			FileList.lastAction();
		}
	});
	$(window).unload(function (){
		$(window).trigger('beforeunload');
	});
});
