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
		$.post('ajax/savefile.php',{ filecontents: filecontents, file: file, dir: dir },function(jsondata){
			if(jsondata.status == 'failure'){
				var answer = confirm(jsondata.data.message);
				if(answer){
					$.post('ajax/savefile.php',{ filecontents: filecontents, file: file, dir: dir, force: 'true' },function(jsondata){
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
