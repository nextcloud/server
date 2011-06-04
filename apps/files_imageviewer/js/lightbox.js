var lightBoxShown=false;
$(document).ready(function() {
	images={};//image cache
	var overlay=$('<div id="lightbox_overlay"/>');
	$( 'body' ).append(overlay);
	var container=$('<div id="lightbox"/>');
	$( 'body' ).append(container);
	FileActions.register('image','View',function(filename){
		var location='ajax/download.php?files='+filename+'&dir='+$('#dir').val();
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
	});
	$( 'body' ).click(hideLightbox);
	FileActions.setDefault('image','View');
});

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
			img.height = maxWidth * ratio;
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

function hideLightbox(){
	if(lightBoxShown){
		$('#lightbox_overlay').hide();
		$('#lightbox').hide();
		lightBoxShown=false;
	}
}