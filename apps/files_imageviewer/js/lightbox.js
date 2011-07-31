
var lightBoxShown=false;
$(document).ready(function() {
	images={};//image cache
	var overlay=$('<div id="lightbox_overlay"/>');
	$( 'body' ).append(overlay);
	var container=$('<div id="lightbox"/>');
	$( 'body' ).append(container);
	$( 'body' ).click(hideLightbox);
	if(typeof FileActions!=='undefined'){
		FileActions.register('image','View','',function(filename){
			viewImage($('#dir').val(),filename);
		});
		FileActions.setDefault('image','View');
	}
	OC.search.customResults.Images=function(row,item){
		var image=item.link.substr(item.link.indexOf('file=')+5);
		var a=row.find('a');
		var container=$('<div id="lightbox"/>');
		a.attr('href','#');
		a.click(function(){
			var file=image.split('/').pop();
			var dir=image.substr(0,image.length-file.length-1);
			viewImage(dir,file);
		});
	}
});

function viewImage(dir,file){
	var location=OC.filePath('files','ajax','download.php')+'?files='+file+'&dir='+dir;
	var overlay=$('#lightbox_overlay');
	var container=$('#lightbox');
	overlay.show();
	if(!images[location]){
		var img = new Image();
		img.onload = function(){
			images[location]=img;
			showLightbox(container,img);
		}
		img.src = location;
	}else{
		showLightbox(container,images[location]);
	}
}

function showLightbox(container,img){
	var maxWidth = $( window ).width() - 50;
	var maxHeight = $( window ).height() - 50;
	if( img.width > maxWidth || img.height > maxHeight ) { // One of these is larger than the window
		var ratio = img.width / img.height;
		if( img.height >= maxHeight ) {
			img.height = maxHeight;
			img.width = maxHeight * ratio;
		} else {
			img.width = maxWidth;
			img.height = maxWidth / ratio;
		}
	}
	container.empty();
	container.append(img);
	container.css('top',Math.round( ($( window ).height() - img.height)/2));
	container.css('left',Math.round( ($( window ).width() - img.width)/2));
	$('#lightbox').show();
	setTimeout(function(){
		lightBoxShown=true;
	},100);
}

function hideLightbox(event){
	if(lightBoxShown){
		event.stopPropagation();
		$('#lightbox_overlay').hide();
		$('#lightbox').hide();
		lightBoxShown=false;
	}
}
