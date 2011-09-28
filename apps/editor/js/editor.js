$(document).ready(function(){
	
	// Set the editor size.
	doEditorResize();
	
	// Initialise the editor
	window.aceEditor = ace.edit("editor");  
	aceEditor.setShowPrintMargin(false);
	
	// Set data type
	var ext = $("#editor").attr('data-type');
	if(ext!='plain'){
		var SyntaxMode = require("ace/mode/"+ext).Mode;
		aceEditor.getSession().setMode(new SyntaxMode());
	}
	
	// Set the theme
	aceEditor.setTheme("ace/theme/cobalt");
	
	// Process the save button click event
	$('#editor_save').click(function(){
		$(this).val('Saving...');
		var filecontents = window.aceEditor.getSession().getValue();
		var dir =  $('#editor').attr('data-dir');
		var file =  $('#editor').attr('data-file');
		$.post('ajax/savefile.php',{ filecontents: filecontents, file: file, dir: dir },function(data){
			if(data=='2'){
				var answer = confirm('The file has been modified after you opened it. Do you want to overwrite the file with your changes?');
			           if(answer){
			               $.post('ajax/savefile.php',{ filecontents: filecontents, file: file, dir: dir, force: 'true' },function(data){
			               	if(data=='1'){
			               		$('#editor_save').val('Save');
			               		$('#editor_save').effect("highlight", {color:'#4BFF8D'}, 3000);
			               	}
			               });
			   			} else {
			   				// Don't save!
			   				$('#editor_save').effect("highlight", {color:'#FF5757'}, 3000);
			   				$('#editor_save').val('Save');	
			   			}
			} else if(data=='1'){
				// Success
				$('#editor_save').val('Save');
				$('#editor_save').effect("highlight", {color:'#4BFF8D'}, 3000);
			}
		});
	// TODO give focus back to the editor
	// window.aceEditor.focus();
	});
	
	/*
	// Process the gotoline button click event
	$('#editor_goToLine').click(function(){
		var html = '<div id="dropdown" class="drop"><input type="text" id="editot_goToLine_line" size="20"><input type="button" id="editor_goToLine_gp" value="<?php echo $l->t(\'Go\'); ?>"></div>';
		$(html).appendTo($('#editor_goToLine'));
		$('#dropdown').show('blind');
		
		//window.aceEditor.gotoLine(100);
		//TODO GIVE FOCUS BACK
	});
	*/
    
    // Process the window resize event 
	$(window).resize(function() {
		doEditorResize();
	});
	
	// Define the editor resize function
	function doEditorResize(){
		$('#editor').css('width',$(window).width()-145+'px');
		$('#editor').css('height',$(window).height()-66+'px');
	}
	
// end doc ready
});
