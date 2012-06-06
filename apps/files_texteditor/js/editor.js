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
	// add file extensions like this: filetype["extension"] = "filetype":
	filetype["h"] = "c_cpp";
	filetype["c"] = "c_cpp";
	filetype["clj"] = "clojure";
	filetype["coffee"] = "coffee"; // coffescript can be compiled to javascript
	filetype["coldfusion"] = "cfc";
	filetype["cpp"] = "c_cpp";
	filetype["cs"] = "csharp";
	filetype["css"] = "css";
	filetype["groovy"] = "groovy";
	filetype["haxe"] = "hx";
	filetype["htm"] = "html";
	filetype["html"] = "html";
	filetype["java"] = "java";
	filetype["js"] = "javascript";
	filetype["jsm"] = "javascript";
	filetype["json"] = "json";
	filetype["latex"] = "latex";
        filetype["less"] = "less";
	filetype["ly"] = "latex";
	filetype["ily"] = "latex";
	filetype["lua"] = "lua";
	filetype["markdown"] = "markdown";
	filetype["md"] = "markdown";
	filetype["mdown"] = "markdown";
	filetype["mdwn"] = "markdown";
	filetype["mkd"] = "markdown";
	filetype["ml"] = "ocaml";
	filetype["mli"] = "ocaml";
	filetype["pl"] = "perl";
	filetype["php"] = "php";
	filetype["powershell"] = "ps1";
	filetype["py"] = "python";
	filetype["rb"] = "ruby";
	filetype["scad"] = "scad"; // seems to be something like 3d model files printed with e.g. reprap
	filetype["scala"] = "scala";
	filetype["scss"] = "scss"; // "sassy css"
        filetype["sh"] = "sh";
	filetype["sql"] = "sql";
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

function showControls(filename,writeperms){
	// Loads the control bar at the top.
	// Load the new toolbar.
	var editorbarhtml = '<div id="editorcontrols" style="display: none;"><div class="crumb svg last" id="breadcrumb_file" style="background-image:url(&quot;'+OC.imagePath('core','breadcrumb.png')+'&quot;)"><p>'+filename.replace(/</, "&lt;").replace(/>/, "&gt;")+'</p></div>';
	if(writeperms=="true"){
		editorbarhtml += '<button id="editor_save">'+t('files_texteditor','Save')+'</button><div class="separator"></div>';
	}
	editorbarhtml += '<label for="editorseachval">Search:</label><input type="text" name="editorsearchval" id="editorsearchval"><div class="separator"></div><button id="editor_close">'+t('files_texteditor','Close')+'</button></div>';
	// Change breadcrumb classes
	$('#controls .last').removeClass('last');
	$('#controls').append(editorbarhtml);
	$('#editorcontrols').fadeIn('slow');
}

function bindControlEvents(){
	$("#editor_save").die('click',doFileSave).live('click',doFileSave);
	$('#editor_close').die('click',hideFileEditor).live('click',hideFileEditor);
	$('#editorsearchval').die('keyup', doSearch).live('keyup', doSearch);
	$('#clearsearchbtn').die('click', resetSearch).live('click', resetSearch);
	$('#nextsearchbtn').die('click', nextSearchResult).live('click', nextSearchResult);
}

// returns true or false if the editor is in view or not
function editorIsShown(){
	// Not working as intended. Always returns true.
	return is_editor_shown;
}

//resets the search
function resetSearch(){
	$('#editorsearchval').val('');
	$('#nextsearchbtn').remove();
	$('#clearsearchbtn').remove();
	window.aceEditor.gotoLine(0);
}

// moves the cursor to the next search resukt
function nextSearchResult(){
	window.aceEditor.findNext();
}
// Performs the initial search
function doSearch(){
	// check if search box empty?
	if($('#editorsearchval').val()==''){
		// Hide clear button
		window.aceEditor.gotoLine(0);
		$('#nextsearchbtn').remove();
		$('#clearsearchbtn').remove();
	} else {
		// New search
		// Reset cursor
		window.aceEditor.gotoLine(0);
		// Do search
		window.aceEditor.find($('#editorsearchval').val(),{
			backwards: false,
			wrap: false,
			caseSensitive: false,
			wholeWord: false,
			regExp: false
		});
		// Show next and clear buttons
		// check if already there
		if($('#nextsearchbtn').length==0){
			var nextbtnhtml = '<button id="nextsearchbtn">Next</button>';
			var clearbtnhtml = '<button id="clearsearchbtn">Clear</button>';
			$('#editorsearchval').after(nextbtnhtml).after(clearbtnhtml);
		}
	}
}

// Tries to save the file.
function doFileSave(){
	if(editorIsShown()){
		// Changed contents?
		if($('#editor').attr('data-edited')=='true'){
			// Get file path
			var path = $('#editor').attr('data-dir')+'/'+$('#editor').attr('data-filename');
			// Get original mtime
			var mtime = $('#editor').attr('data-mtime');
			// Show saving spinner
			$("#editor_save").die('click',doFileSave);
			$('#save_result').remove();
			$('#editor_save').text(t('files_texteditor','Saving...'));
			// Get the data
			var filecontents = window.aceEditor.getSession().getValue();
			// Send the data
			$.post(OC.filePath('files_texteditor','ajax','savefile.php'), { filecontents: filecontents, path: path, mtime: mtime },function(jsondata){
				if(jsondata.status!='success'){
					// Save failed
					$('#editor_save').text(t('files_texteditor','Save'));
					$('#editor_save').after('<p id="save_result" style="float: left">Failed to save file</p>');
					$("#editor_save").live('click',doFileSave);
				} else {
					// Save OK
					// Update mtime
					$('#editor').attr('data-mtime',jsondata.data.mtime);
					$('#editor_save').text(t('files_texteditor','Save'));
					$("#editor_save").live('click',doFileSave);
					// Update titles
					$('#editor').attr('data-edited', 'false');
					$('#breadcrumb_file').text($('#editor').attr('data-filename'));
					document.title = $('#editor').attr('data-filename')+' - ownCloud';
				}
			},'json');
		}
	}
	giveEditorFocus();
};

// Gives the editor focus
function giveEditorFocus(){
	window.aceEditor.focus();
};

// Loads the file editor. Accepts two parameters, dir and filename.
function showFileEditor(dir,filename){
	// Delete any old editors
	$('#editor').remove();
	if(!editorIsShown()){
		// Loads the file editor and display it.
		$('#content').append('<div id="editor"></div>');
		var data = $.getJSON(
			OC.filePath('files_texteditor','ajax','loadfile.php'),
			{file:filename,dir:dir},
			function(result){
				if(result.status == 'success'){
					// Save mtime
					$('#editor').attr('data-mtime', result.data.mtime);
					// Initialise the editor
					$('.actions,#file_action_panel').fadeOut('slow');
					$('table').fadeOut('slow', function() {
						// Show the control bar
						showControls(filename,result.data.write);
						// Update document title
						document.title = filename+' - ownCloud';
						$('#editor').text(result.data.filecontents);
						$('#editor').attr('data-dir', dir);
						$('#editor').attr('data-filename', filename);
						$('#editor').attr('data-edited', 'false');
						window.aceEditor = ace.edit("editor");
						aceEditor.setShowPrintMargin(false);
						aceEditor.getSession().setUseWrapMode(true);
						if(result.data.write=='false'){
							aceEditor.setReadOnly(true);
						}
						setEditorSize();
						setSyntaxMode(getFileExtension(filename));
						OC.addScript('files_texteditor','aceeditor/theme-clouds', function(){
							window.aceEditor.setTheme("ace/theme/clouds");
						});
						window.aceEditor.getSession().on('change', function(){
							if($('#editor').attr('data-edited')!='true'){
								$('#editor').attr('data-edited', 'true');
								$('#breadcrumb_file').text($('#breadcrumb_file').text()+' *');
								document.title = $('#editor').attr('data-filename')+' * - ownCloud';
							}
						});
						// Add the ctrl+s event
						window.aceEditor.commands.addCommand({							name: "save",							bindKey: {							win: "Ctrl-S",							mac: "Command-S",							sender: "editor"							},							exec: function(){
								doFileSave();	
							}						});
					});
				} else {
					// Failed to get the file.
					OC.dialogs.alert(result.data.message, t('files_texteditor','An error occurred!'));
				}
			// End success
			}
		// End ajax
		);
		is_editor_shown = true;
	}
}

// Fades out the editor.
function hideFileEditor(){
	if($('#editor').attr('data-edited') == 'true'){
		// Hide, not remove
		$('#editorcontrols').fadeOut('slow',function(){
			// Check if there is a folder in the breadcrumb
			if($('.crumb.ui-droppable').length){
				$('.crumb.ui-droppable:last').addClass('last');
			}
		});
		// Fade out editor
		$('#editor').fadeOut('slow', function(){
			// Reset document title
			document.title = "ownCloud";
			$('.actions,#file_access_panel').fadeIn('slow');
			$('table').fadeIn('slow');
		});
		$('#notification').text(t('files_texteditor','There were unsaved changes, click here to go back'));
		$('#notification').data('reopeneditor',true);
		$('#notification').fadeIn();
		is_editor_shown = false;
	} else {
		// Remove editor
		$('#editorcontrols').fadeOut('slow',function(){
			$(this).remove();
			$(".crumb:last").addClass('last');
		});
		// Fade out editor
		$('#editor').fadeOut('slow', function(){
			$(this).remove();
			// Reset document title
			document.title = "ownCloud";
			$('.actions,#file_access_panel').fadeIn('slow');
			$('table').fadeIn('slow');
		});
		is_editor_shown = false;
	}
}

// Reopens the last document
function reopenEditor(){
	$('.actions,#file_action_panel').fadeOut('slow');
	$('table').fadeOut('slow', function(){
		$('#controls .last').not('#breadcrumb_file').removeClass('last');
		$('#editor').fadeIn('fast');
		$('#editorcontrols').fadeIn('fast', function(){

		});
	});
	is_editor_shown  = true;
}

// resizes the editor window
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
		var text=item.link.substr(item.link.indexOf('?file=')+6);
		var a=row.find('a');
		a.data('file',text);
		a.attr('href','#');
		a.click(function(){
			var pos=text.lastIndexOf('/')
			var file=text.substr(pos + 1);
			var dir=text.substr(0,pos);
			showFileEditor(dir,file);
		});
	};
	// Binds the file save and close editor events, and gotoline button
	bindControlEvents();
	$('#editor').remove();
	$('#notification').click(function(){
		if($('#notification').data('reopeneditor'))
		{
			reopenEditor();
		}
		$('#notification').fadeOut();
	});
});
