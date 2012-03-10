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
	filetype["html"] = "html";
    filetype["java"] = "java";
	filetype["js"] = "javascript";
    filetype["json"] = "json";
   	filetype["latex"] = "latex";
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
	var editorbarhtml = '<div id="editorcontrols" style="display: none;"><div class="crumb svg last" id="breadcrumb_file" style="background-image:url(&quot;../core/img/breadcrumb.png&quot;)"><p>'+filename+'</p></div>';
	if(writeperms=="true"){
		editorbarhtml += '<button id="editor_save">'+t('files_texteditor','Save')+'</button><div class="separator"></div>';
	}
	editorbarhtml += '<label for="gotolineval">Go to line:</label><input stype="text" id="gotolineval"><label for="editorseachval">Search:</label><input type="text" name="editorsearchval" id="editorsearchval"><div class="separator"></div><button id="editor_close">'+t('files_texteditor','Close')+'</button></div>';
	// Change breadcrumb classes
	$('#controls .last').removeClass('last');
	$('#controls').append(editorbarhtml);
	$('#editorcontrols').fadeIn('slow');
}
 
function bindControlEvents(){
	$("#editor_save").die('click',doFileSave).live('click',doFileSave);	
	$('#editor_close').die('click',hideFileEditor).live('click',hideFileEditor);
	$('#gotolineval').die('keyup', goToLine).live('keyup', goToLine);
	$('#editorsearchval').die('keyup', doSearch).live('keyup', doSearch);
	$('#clearsearchbtn').die('click', resetSearch).live('click', resetSearch);
	$('#nextsearchbtn').die('click', nextSearchResult).live('click', nextSearchResult);
}

// returns true or false if the editor is in view or not
function editorIsShown(){
	// Not working as intended. Always returns true.
	return is_editor_shown;
}

// Moves the editor view to the line number speificed in #gotolineval
function goToLine(){
	// Go to the line specified
	window.aceEditor.gotoLine($('#gotolineval').val());
	
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
			}
		},'json');
	}
};

// Gives the editor focus
function giveEditorFocus(){
	window.aceEditor.focus();
};

// Loads the file editor. Accepts two parameters, dir and filename.
function showFileEditor(dir,filename){
	if(!editorIsShown()){
		// Loads the file editor and display it.
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
						document.title = filename;
						$('#editor').text(result.data.filecontents);
						$('#editor').attr('data-dir', dir);
						$('#editor').attr('data-filename', filename);
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
					});
				} else {
					// Failed to get the file.
					alert(result.data.message);
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
	// Fades out editor controls
	$('#editorcontrols').fadeOut('slow',function(){
		$(this).remove();
		$(".crumb:last").addClass('last');
	});
	// Fade out editor
	$('#editor').fadeOut('slow', function(){
		$(this).remove();
		// Reset document title
		document.title = "ownCloud";
		var editorhtml = '<div id="editor"></div>';
		$('table').after(editorhtml);
		$('.actions,#file_access_panel').fadeIn('slow');
		$('table').fadeIn('slow');	
	});
	is_editor_shown = false;
}

// Keyboard Shortcuts
var ctrlBtn = false;

// TODO fix detection of ctrl keyup
// returns true if ctrl+s or cmd+s is being pressed
function checkForSaveKeyPress(e){
	if(e.which == 17 || e.which == 91) ctrlBtn=true;
	if(e.which == 83 && ctrlBtn == true) {
	e.preventDefault();
	$('#editor_save').trigger('click');
	return false;
		
	}
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
	// Binds the file save and close editor events, and gotoline button
	bindControlEvents();
	
	// Binds the save keyboard shortcut events
	//$(document).unbind('keydown').bind('keydown',checkForSaveKeyPress);
});
