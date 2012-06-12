var uploadingFiles = {};
Files={
	cancelUpload:function(filename) {
		if(uploadingFiles[filename]) {
			uploadingFiles[filename].abort();
			delete uploadingFiles[filename];
			return true;
		}
		return false;
	},
	cancelUploads:function() {
		$.each(uploadingFiles,function(index,file) {
			if(typeof file['abort'] === 'function') {
				file.abort();
				var filename = $('tr').filterAttr('data-file',index);
				filename.hide();
				filename.find('input[type="checkbox"]').removeAttr('checked');
				filename.removeClass('selected');
			} else {
				$.each(file,function(i,f) {
					f.abort();
					delete file[i];
				});
			}
			delete uploadingFiles[index];
		});
		procesSelection();
	}
}
$(document).ready(function() {
	$('#fileList tr').each(function(){
		//little hack to set unescape filenames in attribute
		$(this).attr('data-file',decodeURIComponent($(this).attr('data-file')));
	});

	if($('tr[data-file]').length==0){
		$('.file_upload_filename').addClass('highlight');
	}

	$('#file_action_panel').attr('activeAction', false);

	//drag/drop of files
	$('#fileList tr[data-write="true"] td.filename').draggable(dragOptions);
	$('#fileList tr[data-type="dir"][data-write="true"] td.filename').droppable(folderDropOptions);
	$('div.crumb:not(.last)').droppable(crumbDropOptions);
	$('ul#apps>li:first-child').data('dir','');
	if($('div.crumb').length){
		$('ul#apps>li:first-child').droppable(crumbDropOptions);
	}

	// Triggers invisible file input
	$('.file_upload_button_wrapper').live('click', function() {
		$(this).parent().children('.file_upload_start').trigger('click');
		return false;
	});

	// Sets the file-action buttons behaviour :
	$('tr').live('mouseenter',function(event) {
		FileActions.display($(this).children('td.filename'), $(this).attr('data-file'), $(this).attr('data-type'));
	});
	$('tr').live('mouseleave',function(event) {
		FileActions.hide();
	});

	var lastChecked;

	// Sets the file link behaviour :
	$('td.filename a').live('click',function(event) {
		event.preventDefault();
		if (event.ctrlKey || event.shiftKey) {
			if (event.shiftKey) {
				var last = $(lastChecked).parent().parent().prevAll().length;
				var first = $(this).parent().parent().prevAll().length;
				var start = Math.min(first, last);
				var end = Math.max(first, last);
				var rows = $(this).parent().parent().parent().children('tr');
				for (var i = start; i < end; i++) {
					$(rows).each(function(index) {
						if (index == i) {
							var checkbox = $(this).children().children('input:checkbox');
							$(checkbox).attr('checked', 'checked');
							$(checkbox).parent().parent().addClass('selected');
						}
					});
				}
			}
			var checkbox = $(this).parent().children('input:checkbox');
			lastChecked = checkbox;
			if ($(checkbox).attr('checked')) {
				$(checkbox).removeAttr('checked');
				$(checkbox).parent().parent().removeClass('selected');
				$('#select_all').removeAttr('checked');
			} else {
				$(checkbox).attr('checked', 'checked');
				$(checkbox).parent().parent().toggleClass('selected');
				var selectedCount=$('td.filename input:checkbox:checked').length;
				if (selectedCount == $('td.filename input:checkbox').length) {
					$('#select_all').attr('checked', 'checked');
				}
			}
			procesSelection();
		} else {
			var filename=$(this).parent().parent().attr('data-file');
			var tr=$('tr').filterAttr('data-file',filename);
			var renaming=tr.data('renaming');
			if(!renaming && !FileList.isLoading(filename)){
				var mime=$(this).parent().parent().data('mime');
				var type=$(this).parent().parent().data('type');
				var action=FileActions.getDefault(mime,type);
				if(action){
					action(filename);
				}
			}
		}

	});

	// Sets the select_all checkbox behaviour :
	$('#select_all').click(function() {
		if($(this).attr('checked')){
			// Check all
			$('td.filename input:checkbox').attr('checked', true);
			$('td.filename input:checkbox').parent().parent().addClass('selected');
		}else{
			// Uncheck all
			$('td.filename input:checkbox').attr('checked', false);
			$('td.filename input:checkbox').parent().parent().removeClass('selected');
		}
		procesSelection();
	});

	$('td.filename input:checkbox').live('change',function(event) {
		if (event.shiftKey) {
			var last = $(lastChecked).parent().parent().prevAll().length;
			var first = $(this).parent().parent().prevAll().length;
			var start = Math.min(first, last);
			var end = Math.max(first, last);
			var rows = $(this).parent().parent().parent().children('tr');
			for (var i = start; i < end; i++) {
				$(rows).each(function(index) {
					if (index == i) {
						var checkbox = $(this).children().children('input:checkbox');
						$(checkbox).attr('checked', 'checked');
						$(checkbox).parent().parent().addClass('selected');
					}
				});
			}
		}
		var selectedCount=$('td.filename input:checkbox:checked').length;
		$(this).parent().parent().toggleClass('selected');
		if(!$(this).attr('checked')){
			$('#select_all').attr('checked',false);
		}else{
			if(selectedCount==$('td.filename input:checkbox').length){
				$('#select_all').attr('checked',true);
			}
		}
		procesSelection();
	});

	$('#file_newfolder_name').click(function(){
		if($('#file_newfolder_name').val() == 'New Folder'){
			$('#file_newfolder_name').val('');
		}
	});

	$('.download').click('click',function(event) {
		var files=getSelectedFiles('name').join(';');
		var dir=$('#dir').val()||'/';
		$('#notification').text(t('files','generating ZIP-file, it may take some time.'));
		$('#notification').fadeIn();
		window.location=OC.filePath('files', 'ajax', 'download.php') + '?'+ $.param({ dir: dir, files: files });
		return false;
	});

	$('.delete').click(function(event) {
		var files=getSelectedFiles('name');
		event.preventDefault();
		FileList.do_delete(files);
		return false;
	});

	// drag&drop support using jquery.fileupload
	// TODO use OC.dialogs
	$(document).bind('drop dragover', function (e) {
			e.preventDefault(); // prevent browser from doing anything, if file isn't dropped in dropZone
	});

	$(function() {
		$('.file_upload_start').fileupload({
			dropZone: $('#content'), // restrict dropZone to content div
			add: function(e, data) {
				var files = data.files;
				var totalSize=0;
				if(files){
					for(var i=0;i<files.length;i++){
						totalSize+=files[i].size;
						if(FileList.deleteFiles && FileList.deleteFiles.indexOf(files[i].name)!=-1){//finish delete if we are uploading a deleted file
							FileList.finishDelete(function(){
								$('.file_upload_start').change();
							});
							return;
						}
					}
				}
				if(totalSize>$('#max_upload').val()){
					$( '#uploadsize-message' ).dialog({
						modal: true,
						buttons: {
							Close: function() {
								$( this ).dialog( 'close' );
							}
						}
					});
				}else{
				if($.support.xhrFileUpload) {
					for(var i=0;i<files.length;i++){
						var fileName = files[i].name
						var dropTarget = $(e.originalEvent.target).closest('tr');
						if(dropTarget && dropTarget.attr('data-type') === 'dir') { // drag&drop upload to folder
							var dirName = dropTarget.attr('data-file')
							var jqXHR =  $('.file_upload_start').fileupload('send', {files: files[i],
									formData: function(form) {
										var formArray = form.serializeArray();
										formArray[1]['value'] = dirName;
										return formArray;
									}}).success(function(result, textStatus, jqXHR) {
										var response;
										response=jQuery.parseJSON(result);
										if(response[0] == undefined || response[0].status != 'success') {
											$('#notification').text(t('files', response.data.message));
											$('#notification').fadeIn();
										}
										var file=response[0];
										delete uploadingFiles[dirName][file.name];
										var currentUploads = parseInt(uploadtext.attr('currentUploads'));
										currentUploads -= 1;
										uploadtext.attr('currentUploads', currentUploads);
										if(currentUploads === 0) {
											var img = OC.imagePath('core', 'filetypes/folder.png');
											var tr=$('tr').filterAttr('data-file',dirName);
											tr.find('td.filename').attr('style','background-image:url('+img+')');
											uploadtext.text('');
											uploadtext.hide();
										} else {
											uploadtext.text(currentUploads + ' files uploading')
										}
									})
							.error(function(jqXHR, textStatus, errorThrown) {
								if(errorThrown === 'abort') {
									var currentUploads = parseInt(uploadtext.attr('currentUploads'));
									currentUploads -= 1;
									uploadtext.attr('currentUploads', currentUploads);
									if(currentUploads === 0) {
										var img = OC.imagePath('core', 'filetypes/folder.png');
										var tr=$('tr').filterAttr('data-file',dirName);
										tr.find('td.filename').attr('style','background-image:url('+img+')');
										uploadtext.text('');
										uploadtext.hide();
									} else {
										uploadtext.text(currentUploads + ' files uploading')
									}
									$('#notification').hide();
									$('#notification').text(t('files', 'Upload cancelled.'));
									$('#notification').fadeIn();
								}
							});
							//TODO test with filenames containing slashes
							if(uploadingFiles[dirName] === undefined) {
								uploadingFiles[dirName] = {};
							}
							uploadingFiles[dirName][fileName] = jqXHR;
						} else {
							var jqXHR =  $('.file_upload_start').fileupload('send', {files: files[i]})
									.success(function(result, textStatus, jqXHR) {
										var response;
										response=jQuery.parseJSON(result);
										if(response[0] != undefined && response[0].status == 'success') {
											var file=response[0];
											delete uploadingFiles[file.name];
											$('tr').filterAttr('data-file',file.name).data('mime',file.mime);
											var size = $('tr').filterAttr('data-file',file.name).find('td.filesize').text();
											if(size==t('files','Pending')){
												$('tr').filterAttr('data-file',file.name).find('td.filesize').text(file.size);
											}
											FileList.loadingDone(file.name);
										} else {
											$('#notification').text(t('files', response.data.message));
											$('#notification').fadeIn();
											$('#fileList > tr').not('[data-mime]').fadeOut();
											$('#fileList > tr').not('[data-mime]').remove();
										}
									})
							.error(function(jqXHR, textStatus, errorThrown) {
								if(errorThrown === 'abort') {
									$('#notification').hide();
									$('#notification').text(t('files', 'Upload cancelled.'));
									$('#notification').fadeIn();
								}
							});
							uploadingFiles[files[i].name] = jqXHR;
						}
					}
				}else{
					data.submit().success(function(data, status) {
						response = jQuery.parseJSON(data[0].body.innerText);
						if(response[0] != undefined && response[0].status == 'success') {
							var file=response[0];
							delete uploadingFiles[file.name];
							$('tr').filterAttr('data-file',file.name).data('mime',file.mime);
							var size = $('tr').filterAttr('data-file',file.name).find('td.filesize').text();
							if(size==t('files','Pending')){
								$('tr').filterAttr('data-file',file.name).find('td.filesize').text(file.size);
							}
							FileList.loadingDone(file.name);
						} else {
							$('#notification').text(t('files', response.data.message));
							$('#notification').fadeIn();
							$('#fileList > tr').not('[data-mime]').fadeOut();
							$('#fileList > tr').not('[data-mime]').remove();
						}
					});
				}
									
					var date=new Date();
					if(files){
						for(var i=0;i<files.length;i++){
							if(files[i].size>0){
								var size=files[i].size;
							}else{
								var size=t('files','Pending');
							}
							if(files && !dirName){
						FileList.addFile(getUniqueName(files[i].name),size,date,true);
							} else if(dirName) {
								var uploadtext = $('tr').filterAttr('data-type', 'dir').filterAttr('data-file', dirName).find('.uploadtext')
								var currentUploads = parseInt(uploadtext.attr('currentUploads'));
								currentUploads += 1;
								uploadtext.attr('currentUploads', currentUploads);
								if(currentUploads === 1) {
									var img = OC.imagePath('core', 'loading.gif');
									var tr=$('tr').filterAttr('data-file',dirName);
									tr.find('td.filename').attr('style','background-image:url('+img+')');
									uploadtext.text('1 file uploading');
									uploadtext.show();
								} else {
									uploadtext.text(currentUploads + ' files uploading')
								}
							}
						}
					}else{
						var filename=this.value.split('\\').pop(); //ie prepends C:\fakepath\ in front of the filename
				FileList.addFile(getUniqueName(filename),'Pending',date,true);
					}
				}
			},
			fail: function(e, data) {
				// TODO: cancel upload & display error notification
			},
			progress: function(e, data) {
				// TODO: show nice progress bar in file row
			},
			progressall: function(e, data) {
				var progress = (data.loaded/data.total)*100;
				$('#uploadprogressbar').progressbar('value',progress);
			},
			start: function(e, data) {
				$('#uploadprogressbar').progressbar({value:0});
				$('#uploadprogressbar').fadeIn();
				if(data.dataType != 'iframe ') {
					$('#upload input.stop').show();
				}
			},
			stop: function(e, data) {
				if(data.dataType != 'iframe ') {
					$('#upload input.stop').hide();
				}	
				$('#uploadprogressbar').progressbar('value',100);
				$('#uploadprogressbar').fadeOut();
			}
		})
	});

	//add multiply file upload attribute to all browsers except konqueror (which crashes when it's used)
	if(navigator.userAgent.search(/konqueror/i)==-1){
		$('.file_upload_start').attr('multiple','multiple')
	}

	//if the breadcrumb is to long, start by replacing foldernames with '...' except for the current folder
	var crumb=$('div.crumb').first();
	while($('div.controls').height()>40 && crumb.next('div.crumb').length>0){
		crumb.children('a').text('...');
		crumb=crumb.next('div.crumb');
	}
	//if that isn't enough, start removing items from the breacrumb except for the current folder and it's parent
	var crumb=$('div.crumb').first();
	var next=crumb.next('div.crumb');
	while($('div.controls').height()>40 && next.next('div.crumb').length>0){
		crumb.remove();
		crumb=next;
		next=crumb.next('div.crumb');
	}
	//still not enough, start shorting down the current folder name
	var crumb=$('div.crumb>a').last();
	while($('div.controls').height()>40 && crumb.text().length>6){
		var text=crumb.text()
		text=text.substr(0,text.length-6)+'...';
		crumb.text(text);
	}

	$(window).click(function(){
		$('#new>ul').hide();
		$('#new').removeClass('active');
		$('button.file_upload_filename').removeClass('active');
		$('#new li').each(function(i,element){
			if($(element).children('p').length==0){
				$(element).children('input').remove();
				$(element).append('<p>'+$(element).data('text')+'</p>');
			}
		});
	});
	$('#new').click(function(event){
		event.stopPropagation();
	});
	$('#new>a').click(function(){
		$('#new>ul').toggle();
		$('#new').toggleClass('active');
		$('button.file_upload_filename').toggleClass('active');
	});
	$('#new li').click(function(){
		if($(this).children('p').length==0){
			return;
		}

		$('#new li').each(function(i,element){
			if($(element).children('p').length==0){
				$(element).children('input').remove();
				$(element).append('<p>'+$(element).data('text')+'</p>');
			}
		});

		var type=$(this).data('type');
		var text=$(this).children('p').text();
		$(this).data('text',text);
		$(this).children('p').remove();
		var input=$('<input>');
		$(this).append(input);
		input.focus();
		input.change(function(){
			var name=$(this).val();
			if(type != 'web' && name.indexOf('/')!=-1){
				$('#notification').text(t('files','Invalid name, \'/\' is not allowed.'));
				$('#notification').fadeIn();
				return;
			}
			switch(type){
				case 'file':
					$.post(
						OC.filePath('files','ajax','newfile.php'),
						{dir:$('#dir').val(),filename:name,content:" \n"},
						function(data){
							var date=new Date();
							FileList.addFile(name,0,date);
							var tr=$('tr').filterAttr('data-file',name);
							tr.data('mime','text/plain');
							getMimeIcon('text/plain',function(path){
								tr.find('td.filename').attr('style','background-image:url('+path+')');
							});
						}
					);
					break;
				case 'folder':
					$.post(
						OC.filePath('files','ajax','newfolder.php'),
						{dir:$('#dir').val(),foldername:name},
						function(data){
							var date=new Date();
							FileList.addDir(name,0,date);
						}
					);
					break;
				case 'web':
					if(name.substr(0,8)!='https://' && name.substr(0,7)!='http://'){
						name='http://'.name;
					}
					var localName=name;
					if(localName.substr(localName.length-1,1)=='/'){//strip /
						localName=localName.substr(0,localName.length-1)
					}
					if(localName.indexOf('/')){//use last part of url
						localName=localName.split('/').pop();
					}else{//or the domain
						localName=(localName.match(/:\/\/(.[^/]+)/)[1]).replace('www.','');
					}
					$.post(
						OC.filePath('files','ajax','newfile.php'),
						{dir:$('#dir').val(),source:name,filename:localName},
						function(result){
							if(result.status == 'success'){
								var date=new Date();
								FileList.addFile(localName,0,date);
								var tr=$('tr').filterAttr('data-file',localName);
								tr.data('mime',result.data.mime);
								getMimeIcon(result.data.mime,function(path){
									tr.find('td.filename').attr('style','background-image:url('+path+')');
								});
							}else{

							}
						}
					);
					break;
			}
			var li=$(this).parent();
			$(this).remove();
			li.append('<p>'+li.data('text')+'</p>');
			$('#new>a').click();
		});
	});

	//check if we need to scan the filesystem
	$.get(OC.filePath('files','ajax','scan.php'),{checkonly:'true'}, function(response) {
		if(response.data.done){
			scanFiles();
		}
	}, "json");
});

function scanFiles(force,dir){
	if(!dir){
		dir='';
	}
	force=!!force; //cast to bool
	scanFiles.scanning=true;
	$('#scanning-message').show();
	$('#fileList').remove();
	var scannerEventSource=new OC.EventSource(OC.filePath('files','ajax','scan.php'),{force:force,dir:dir});
	scanFiles.cancel=scannerEventSource.close.bind(scannerEventSource);
	scannerEventSource.listen('scanning',function(data){
		$('#scan-count').text(data.count+' files scanned');
		$('#scan-current').text(data.file+'/');
	});
	scannerEventSource.listen('success',function(success){
		scanFiles.scanning=false;
		if(success){
			window.location.reload();
		}else{
			alert('error while scanning');
		}
	});
}
scanFiles.scanning=false;

function boolOperationFinished(data, callback) {
	result = jQuery.parseJSON(data.responseText);
	if(result.status == 'success'){
		callback.call();
	} else {
		alert(result.data.message);
	}
}

function updateBreadcrumb(breadcrumbHtml) {
	$('p.nav').empty().html(breadcrumbHtml);
}

//options for file drag/dropp
var dragOptions={
	distance: 20, revert: 'invalid', opacity: 0.7,
	stop: function(event, ui) {
		$('#fileList tr td.filename').addClass('ui-draggable');
	}
};
var folderDropOptions={
	drop: function( event, ui ) {
		var file=ui.draggable.parent().data('file');
		var target=$(this).find('.nametext').text().trim();
		var dir=$('#dir').val();
		$.ajax({
			url: OC.filePath('files', 'ajax', 'move.php'),
			data: "dir="+encodeURIComponent(dir)+"&file="+encodeURIComponent(file)+'&target='+encodeURIComponent(dir)+'/'+encodeURIComponent(target),
			complete: function(data){boolOperationFinished(data, function(){
				var el = $('#fileList tr').filterAttr('data-file',file).find('td.filename');
				el.draggable('destroy');
				FileList.remove(file);
			});}
		});
	}
}
var crumbDropOptions={
	drop: function( event, ui ) {
		var file=ui.draggable.text().trim();
		var target=$(this).data('dir');
		var dir=$('#dir').val();
		while(dir.substr(0,1)=='/'){//remove extra leading /'s
				dir=dir.substr(1);
		}
		dir='/'+dir;
		if(dir.substr(-1,1)!='/'){
			dir=dir+'/';
		}
		if(target==dir || target+'/'==dir){
			return;
		}
		$.ajax({
			url: OC.filePath('files', 'ajax', 'move.php'),
		 data: "dir="+encodeURIComponent(dir)+"&file="+encodeURIComponent(file)+'&target='+encodeURIComponent(target),
		 complete: function(data){boolOperationFinished(data, function(){
			 FileList.remove(file);
		 });}
		});
	},
	tolerance: 'pointer'
}

function procesSelection(){
	var selected=getSelectedFiles();
	var selectedFiles=selected.filter(function(el){return el.type=='file'});
	var selectedFolders=selected.filter(function(el){return el.type=='dir'});
	if(selectedFiles.length==0 && selectedFolders.length==0){
		$('#headerName>span.name').text(t('files','Name'));
		$('#headerSize').text(t('files','Size'));
		$('#modified').text(t('files','Modified'));
		$('th').removeClass('multiselect');
		$('.selectedActions').hide();
		$('thead').removeClass('fixed');
		$('#headerName').css('width','auto');
		$('#headerSize').css('width','auto');
		$('#headerDate').css('width','auto');
		$('table').css('padding-top','0');
	}else{
		var width={name:$('#headerName').css('width'),size:$('#headerSize').css('width'),date:$('#headerDate').css('width')};
		$('#headerName').css('width',width.name);
		$('#headerSize').css('width',width.size);
		$('#headerDate').css('width',width.date);
		$('.selectedActions').show();
		var totalSize=0;
		for(var i=0;i<selectedFiles.length;i++){
			totalSize+=selectedFiles[i].size;
		};
		for(var i=0;i<selectedFolders.length;i++){
			totalSize+=selectedFolders[i].size;
		};
		simpleSize=simpleFileSize(totalSize);
		$('#headerSize').text(simpleSize);
		$('#headerSize').attr('title',humanFileSize(totalSize));
		var selection='';
		if(selectedFolders.length>0){
			if(selectedFolders.length==1){
				selection+='1 '+t('files','folder');
			}else{
				selection+=selectedFolders.length+' '+t('files','folders');
			}
			if(selectedFiles.length>0){
				selection+=' & ';
			}
		}
		if(selectedFiles.length>0){
			if(selectedFiles.length==1){
				selection+='1 '+t('files','file');
			}else{
				selection+=selectedFiles.length+' '+t('files','files');
			}
		}
		$('#headerName>span.name').text(selection);
		$('#modified').text('');
		$('th').addClass('multiselect');
	}
}

/**
 * @brief get a list of selected files
 * @param string property (option) the property of the file requested
 * @return array
 *
 * possible values for property: name, mime, size and type
 * if property is set, an array with that property for each file is returnd
 * if it's ommited an array of objects with all properties is returned
 */
function getSelectedFiles(property){
	var elements=$('td.filename input:checkbox:checked').parent().parent();
	var files=[];
	elements.each(function(i,element){
		var file={
			name:$(element).attr('data-file'),
			mime:$(element).data('mime'),
			type:$(element).data('type'),
			size:$(element).data('size'),
		};
		if(property){
			files.push(file[property]);
		}else{
			files.push(file);
		}
	});
	return files;
}

function relative_modified_date(timestamp) {
	var timediff = Math.round((new Date()).getTime() / 1000) - timestamp;
	var diffminutes = Math.round(timediff/60);
	var diffhours = Math.round(diffminutes/60);
	var diffdays = Math.round(diffhours/24);
	var diffmonths = Math.round(diffdays/31);
	var diffyears = Math.round(diffdays/365);
	if(timediff < 60) { return t('files','seconds ago'); }
	else if(timediff < 120) { return '1 '+t('files','minute ago'); }
	else if(timediff < 3600) { return diffminutes+' '+t('files','minutes ago'); }
	//else if($timediff < 7200) { return '1 hour ago'; }
	//else if($timediff < 86400) { return $diffhours.' hours ago'; }
	else if(timediff < 86400) { return t('files','today'); }
	else if(timediff < 172800) { return t('files','yesterday'); }
	else if(timediff < 2678400) { return diffdays+' '+t('files','days ago'); }
	else if(timediff < 5184000) { return t('files','last month'); }
	//else if($timediff < 31556926) { return $diffmonths.' months ago'; }
	else if(timediff < 31556926) { return t('files','months ago'); }
	else if(timediff < 63113852) { return t('files','last year'); }
	else { return diffyears+' '+t('files','years ago'); }
}

function getMimeIcon(mime, ready){
	if(getMimeIcon.cache[mime]){
		ready(getMimeIcon.cache[mime]);
	}else{
		$.get( OC.filePath('files','ajax','mimeicon.php')+'?mime='+mime, function(path){
			getMimeIcon.cache[mime]=path;
			ready(getMimeIcon.cache[mime]);
		});
	}
}
getMimeIcon.cache={};

function getUniqueName(name){
	if($('tr').filterAttr('data-file',name).length>0){
		var parts=name.split('.');
		var extension=parts.pop();
		var base=parts.join('.');
		numMatch=base.match(/\((\d+)\)/);
		var num=2;
		if(numMatch && numMatch.length>0){
			num=parseInt(numMatch[numMatch.length-1])+1;
			base=base.split('(')
			base.pop();
			base=base.join('(').trim();
		}
		name=base+' ('+num+').'+extension;
		return getUniqueName(name);
	}
	return name;
}
