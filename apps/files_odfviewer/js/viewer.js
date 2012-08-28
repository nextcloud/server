function viewOdf(dir, file) {
	var location=OC.filePath('files','ajax','download.php')+'?files='+file+'&dir='+dir;

	// fade out files menu and add odf menu
	$('.actions,#file_action_panel').fadeOut('slow').promise().done(function() {
		// odf action toolbar
		var odfToolbarHtml = 
			'<div id="odf-toolbar">' +
			'<input type="button" id="odf_close" value="Close">' +
			'</div>';
		$('#controls').append(odfToolbarHtml);

	});

	// fade out file list and show pdf canvas
	$('table').fadeOut('slow').promise().done(function(){;
		var canvashtml = '<div id="odf-canvas"></div>';
		$('table').after(canvashtml);

		var odfelement = document.getElementById("odf-canvas");
		var odfcanvas = new odf.OdfCanvas(odfelement);
		odfcanvas.load(location);
	});
}

function closeOdfViewer(){
	// Fade out odf-toolbar
	$('#odf-toolbar').fadeOut('slow');
	// Fade out editor
	$('#odf-canvas').fadeOut('slow', function(){
		$('#odf-toolbar').remove();
		$('#odf-canvas').remove();
		$('.actions,#file_access_panel').fadeIn('slow');
		$('table').fadeIn('slow');	
	});
	is_editor_shown = false;
}

$(document).ready(function() {
	if(typeof FileActions!=='undefined'){

		var supportedMimes = new Array(
			'application/vnd.oasis.opendocument.text', 
			'application/vnd.oasis.opendocument.spreadsheet',
			'application/vnd.oasis.opendocument.graphics',
			'application/vnd.oasis.opendocument.presentation');
		for (var i = 0; i < supportedMimes.length; ++i){
			var mime = supportedMimes[i];
			FileActions.register(mime,'View','',function(filename){
				viewOdf($('#dir').val(),filename);
			});
			FileActions.setDefault(mime,'View');
		}
	}
	
	$('#odf_close').live('click',function() {
		closeOdfViewer();	
	});
});

