$(document).ready(function() {
	if(typeof FileActions!=='undefined'){
		FileActions.register('image','View','',function(filename){
			viewImage($('#dir').val(),filename);
		});
		FileActions.setDefault('image','View');
	}
	OC.search.customResults.Images=function(row,item){
		var image=item.link.substr(item.link.indexOf('file=')+5);
		var a=row.find('a');
		a.attr('href','#');
		a.click(function(){
			var file=image.split('/').pop();
			var dir=image.substr(0,image.length-file.length-1);
			viewImage(dir,file);
		});
	}
});

function viewImage(dir, file) {
	var location=OC.filePath('files','ajax','download.php')+'?files='+file+'&dir='+dir;
	$.fancybox({
		"href": location,
		"title": file,
		"titlePosition": "inside"
	});
}
