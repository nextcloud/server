function setEditorSize(){
	// Sets the size of the text editor window.
	$('#editor').css('height', $(window).height()-90);
	$('#editor').css('width', $(window).width()-180);
	$('#editor').css('padding-top', '40px');		
}

function getFileExtension(file){
	var parts=file.split('.');
	return parts[parts.length-1];
}

function setSyntaxMode(ext){
	// Loads the syntax mode files and tells the editor
	var filetype = new Array()
	// Todo finish these
	filetype["php"] = "php";
	filetype["html"] = "html";
	filetype["rb"] = "ruby";
	filetype["css"] = "css";
	filetype["pl"] = "perl";
	filetype["py"] = "python";
	filetype["xml"] = "xml";
	filetype["js"] = "javascript";

	if(filetype[ext]!=null){
		// Then it must be in the array, so load the custom syntax mode
		// Set the syntax mode
		OC.addScript('files_texteditor','aceeditor/mode-'+filetype[ext], function(){
			var SyntaxMode = require("ace/mode/"+filetype[ext]).Mode;
			window.aceEditor.getSession().setMode(new SyntaxMode());
		});
	}	
}

function showControlBar(){
	// Loads the control bar at the top.
	$('.actions,#file_action_panel').fadeOut('slow', function(){
		// Load the new toolbar.
		var html = '<div id="editorbar"><input type="button" id="editor_save" value="'+t('files_texteditor','Save')+'"><input type="button" id="editor_close" value="Close"></div>';
		if($('#editorbar').length==0){
			$('#controls').append(html).fadeIn('slow');	
		}
		bindControlEvents();
	});
}

function bindControlEvents(){
	$('#editor_save').bind('click', function() {
		$(this).val('Saving...');
		var filecontents = window.aceEditor.getSession().getValue();
		var dir =  $('#editor').attr('data-dir');
		var file =  $('#editor').attr('data-file');
		$.post('http://cloud.tomneedham.com/apps/files_texteditor/ajax/savefile.php', { filecontents: filecontents, file: file, dir: dir },function(jsondata){
			if(jsondata.status == 'failure'){
				var answer = confirm(jsondata.data.message);
				if(answer){
					$.post(OC.filePath('apps','files_texteditor','ajax','savefile.php'),{ filecontents: filecontents, file: file, dir: dir, force: 'true' },function(jsondata){
						if(jsondata.status =='success'){
							$('#editor_save').val('Save');
							$('#editor_save').effect("highlight", {color:'#4BFF8D'}, 3000);
						} 
						else {
							// Save error
							alert(jsondata.data.message);	
						}
					}, 'json');
				} 
		   		else {
					// Don't save!
					$('#editor_save').effect("highlight", {color:'#FF5757'}, 3000);
					$('#editor_save').val('Save');	
		   		}
			} 
			else if(jsondata.status == 'success'){
				// Success
				$('#editor_save').val('Save');
				$('#editor_save').effect("highlight", {color:'#4BFF8D'}, 3000);
			}
		}, 'json');
	// TODO give focus back to the editor
	// window.aceEditor.focus();
	});	
	
	$('#editor_close').bind('click', function() {
		hideFileEditor();	
	});
}

function showFileEditor(dir,filename){
	// Loads the file editor and display it.
	var data = $.ajax({
			url: OC.filePath('files','ajax','download.php')+'?files='+encodeURIComponent(filename)+'&dir='+encodeURIComponent(dir),
			complete: function(data){
				var data = data.responseText;
				// Initialise the editor
				showControlBar();
				$('table').fadeOut('slow', function() {
					$('#editor').html(data);
					// encodeURIComponenet?
					$('#editor').attr('data-dir', dir);
					$('#editor').attr('data-filename', filename);
					window.aceEditor = ace.edit("editor");  
					aceEditor.setShowPrintMargin(false);
					setSyntaxMode(getFileExtension(filename));
					OC.addScript('files_texteditor','aceeditor/theme-clouds', function(){
						window.aceEditor.setTheme("ace/theme/clouds");
					});
					showControlBar();
				});
			// End success
			}
			// End ajax
			});
	setEditorSize();
}

function hideFileEditor(){
	$('#editorbar').fadeOut('slow');
	$('#editor').fadeOut('slow', function(){
		$('#editorbar').html('');
		$('#editor').html('');
		$('.actions,#file_access_panel').fadeIn('slow');
		$('table').fadeIn('slow');	
	});
}

$(window).resize(function() {
  setEditorSize();
});