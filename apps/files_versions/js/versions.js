$(document).ready(function(){
	
	// Add history button to files/index.php
	FileActions.register('file','History',function(){return OC.imagePath('core','actions/history')},function(filename){
		window.location='../apps/files_versions/history.php?path='+encodeURIComponent($('#dir').val()).replace(/%2F/g, '/')+'/'+encodeURIComponent(filename);
	});
	
});

