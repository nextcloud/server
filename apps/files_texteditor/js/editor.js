function setEditorSize(){
	// Sets the size of the text editor window.
	fillWindow($('#editor'));
}

function getFileExtension(file){
	var parts=file.split('.');
	return parts[parts.length-1];
}

function setSyntaxMode(ext){
	// Loads the syntax mode files and tells the editor
	var filetype = new Array();
	// Todo finish these
    filetype["h"] = "c_cpp";
    filetype["c"] = "c_cpp";
    filetype["cpp"] = "c_cpp";
    filetype["clj"] = "clojure";
    filetype["coffee"] = "coffee"; // coffescript can be compiled to javascript
    filetype["cs"] = "csharp";
	filetype["css"] = "css";
    filetype["groovy"] = "groovy";
	filetype["html"] = "html";
    filetype["java"] = "java";
	filetype["js"] = "javascript";
    filetype["json"] = "json";
    filetype["ml"] = "ocaml";
    filetype["mli"] = "ocaml";
	filetype["pl"] = "perl";
	filetype["php"] = "php";
	filetype["py"] = "python";
	filetype["rb"] = "ruby";
    filetype["scad"] = "scad"; // seems to be something like 3d model files printed with e.g. reprap
    filetype["scala"] = "scala";
    filetype["scss"] = "scss"; // "sassy css"
    filetype["svg"] = "svg";
    filetype["textile"] = "textile"; // related to markdown
	filetype["xml"] = "xml";

	if(filetype[ext]!=null){
		// Then it must be in the array, so load the custom syntax mode
		// Set the syntax mode
		OC.addScript('files_texteditor','aceeditor/mode-'+filetype[ext], function(){
			var SyntaxMode = require("ace/mode/"+filetype[ext]).Mode;
			window.aceEditor.getSession().setMode(new SyntaxMode());
		});
	}	
}

function showControls(filename){
	// Loads the control bar at the top.
	$('.actions,#file_action_panel').fadeOut('slow').promise().done(function() {
		// Load the new toolbar.
		var savebtnhtml = '<input type="button" id="editor_save" value="'+t('files_texteditor','Save')+'">';
		var html = '<input type="button" id="editor_close" value="Close">';
		$('#controls').append(html);
		$('#editorbar').fadeIn('slow');	
		var breadcrumbhtml = '<div class="crumb svg" id="breadcrumb_file" style="background-image:url(&quot;../core/img/breadcrumb.png&quot;)"><p>'+filename+'</p></div>';
		$('.actions').before(breadcrumbhtml).before(savebtnhtml);
	});
}
 
function bindControlEvents(){
	$("#editor_save").die('click',doFileSave).live('click',doFileSave);	
	$('#editor_close').die('click',hideFileEditor).live('click',hideFileEditor);
}

function editorIsShown(){
	// Not working as intended. Always returns true.
	return is_editor_shown;
}

function updateSessionFileHash(path){
	$.get(OC.filePath('files_texteditor','ajax','loadfile.php'),
		{ path: path },
   		function(jsondata){
   			if(jsondata.status=='failure'){
   				alert('Failed to update session file hash.');	
   			}
   	}, "json");}

function doFileSave(){
	if(editorIsShown()){
		$("#editor_save").die('click',doFileSave);
		$('#editor_save').after('<img id="saving_icon" src="'+OC.filePath('core','img','loading.gif')+'"></img>');
			var filecontents = window.aceEditor.getSession().getValue();
			var dir =  $('#editor').attr('data-dir');
			var file =  $('#editor').attr('data-filename');
			$.post(OC.filePath('files_texteditor','ajax','savefile.php'), { filecontents: filecontents, file: file, dir: dir },function(jsondata){
			
				if(jsondata.status == 'failure'){
					var answer = confirm(jsondata.data.message);
					if(answer){
						$.post(OC.filePath('files_texteditor','ajax','savefile.php'),{ filecontents: filecontents, file: file, dir: dir, force: 'true' },function(jsondata){
							if(jsondata.status =='success'){
								$('#saving_icon').remove();
								$('#editor_save').after('<p id="save_result" style="float: left">Saved!</p>')
								setTimeout(function() {
  									$('#save_result').fadeOut('slow',function(){
  										$(this).remove();	
  										$("#editor_save").live('click',doFileSave);
  									});
								}, 2000);
							} 
							else {
								// Save error
								$('#saving_icon').remove();
								$('#editor_save').after('<p id="save_result" style="float: left">Failed!</p>');
								setTimeout(function() {
									$('#save_result').fadeOut('slow',function(){ 
										$(this).remove();
										$("#editor_save").live('click',doFileSave); 
									});
								}, 2000);	
							}
						}, 'json');
					} 
		   			else {
						// Don't save!
						$('#saving_icon').remove();
						// Temporary measure until we get a tick icon
						$('#editor_save').after('<p id="save_result" style="float: left">Saved!</p>');
						setTimeout(function() {
									$('#save_result').fadeOut('slow',function(){ 
										$(this).remove(); 
										$("#editor_save").live('click',doFileSave);
									});
						}, 2000);
			   		}
				} 
				else if(jsondata.status == 'success'){
					// Success
					$('#saving_icon').remove();
					// Temporary measure until we get a tick icon
					$('#editor_save').after('<p id="save_result" style="float: left">Saved!</p>');
					setTimeout(function() {
						$('#save_result').fadeOut('slow',function(){ 
							$(this).remove(); 
							$("#editor_save").live('click',doFileSave);
						});
					}, 2000);
				}
			}, 'json');
		giveEditorFocus();
	} else {
		return;	
	}	
};

function giveEditorFocus(){
	window.aceEditor.focus();
};

function showFileEditor(dir,filename){
	if(!editorIsShown()){
		// Loads the file editor and display it.
		var data = $.ajax({
				url: OC.filePath('files','ajax','download.php')+'?files='+encodeURIComponent(filename)+'&dir='+encodeURIComponent(dir),
				complete: function(data){
					// Initialise the editor
					updateSessionFileHash(dir+'/'+filename);
					showControls(filename);
					$('table').fadeOut('slow', function() {
						$('#editor').text(data.responseText);
						// encodeURIComponenet?
						$('#editor').attr('data-dir', dir);
						$('#editor').attr('data-filename', filename);
						window.aceEditor = ace.edit("editor");  
						aceEditor.setShowPrintMargin(false);
						setEditorSize();
						setSyntaxMode(getFileExtension(filename));
						OC.addScript('files_texteditor','aceeditor/theme-clouds', function(){
							window.aceEditor.setTheme("ace/theme/clouds");
						});
					});
				// End success
				}
				// End ajax
				});
		is_editor_shown = true;
	}
}

function hideFileEditor(){
	// Fade out controls
	$('#editor_close').fadeOut('slow');
	// Fade out the save button
	$('#editor_save').fadeOut('slow');
	// Fade out breadcrumb
	$('#breadcrumb_file').fadeOut('slow', function(){ $(this).remove();});
	// Fade out editor
	$('#editor').fadeOut('slow', function(){
		$('#editor_close').remove();
		$('#editor_save').remove();
		$('#editor').remove();
		var editorhtml = '<div id="editor"></div>';
		$('table').after(editorhtml);
		$('.actions,#file_access_panel').fadeIn('slow');
		$('table').fadeIn('slow');	
	});
	is_editor_shown = false;
}

// Keyboard Shortcuts
var ctrlBtn = false;

function checkForCtrlKey(e){
		if(e.which == 17 || e.which == 91) ctrlBtn=false;
	}
	
function checkForSaveKeyPress(e){
		if(e.which == 17 || e.which == 91) ctrlBtn=true;
   		if(e.which == 83 && ctrlBtn == true) {
   			e.preventDefault();
        	$('#editor_save').trigger('click');
			return false;
   		}
}

$(window).resize(function() {
	setEditorSize();
});
var is_editor_shown = false;
$(document).ready(function(){
	if(typeof FileActions!=='undefined'){
		FileActions.register('text','Edit','',function(filename){
			showFileEditor($('#dir').val(),filename);
		});
		FileActions.setDefault('text','Edit');
		FileActions.register('application/xml','Edit','',function(filename){
			showFileEditor($('#dir').val(),filename);
		});
		FileActions.setDefault('application/xml','Edit');
	}
	OC.search.customResults.Text=function(row,item){
		var text=item.link.substr(item.link.indexOf('file=')+5);
		var a=row.find('a');
		a.data('file',text);
		a.attr('href','#');
		a.click(function(){
			var file=text.split('/').pop();
			var dir=text.substr(0,text.length-file.length-1);
			showFileEditor(dir,file);
		});
	}
	// Binds the file save and close editor events to the buttons
	bindControlEvents();
	
	// Binds the save keyboard shortcut events
	$(document).unbind('keyup').bind('keyup',checkForCtrlKey).unbind('keydown').bind('keydown',checkForSaveKeyPress);
});
