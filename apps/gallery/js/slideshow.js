$(document).ready(function(){
        
	$.endSlideshow = function () {
		if($.supersized.vars.slideshow_interval){
			clearInterval($.supersized.vars.slideshow_interval);
		};

		$('#supersized-holder').remove();
		$('#slideshow-content').hide();
		$('#thumb-list').remove();
	}
        
	// add slideshow in holder div
	$('#slideshow input.start').click(function(){

		var images=[];
		$('#gallerycontent div a').each(function(i,a){
			images.push({image : a.href, title : a.title.replace(/</, '&lt;').replace(/>/, '&gt;'), thumb : a.children[0].src, url : 'javascript:$.endSlideshow()'});
		});

		if (images.length <= 0) {
			return;
		}

		$('body').append("<div id='supersized-holder'></div>");
		$('#supersized-loader').remove();
		$('#supersized').remove();
		$('#supersized-holder').append("<div id='supersized-loader'></div><ul id='supersized'></ul>");
		$('#supersized').show();
		$('#slideshow-content').show();


		jQuery(function($){

			$.supersized({

				// Functionality
				slide_interval      :   3000,		// Length between transitions
				transition          :   1, 		// 0-None, 1-Fade, 2-Slide Top, 3-Slide Right, 4-Slide Bottom, 5-Slide Left, 6-Carousel Right, 7-Carousel Left
				transition_speed    :   700,		// Speed of transition

				// Components							
				slide_links         :   'blank',	// Individual links for each slide (Options: false, 'num', 'name', 'blank')
				slides              :   images		// Slideshow Images
							    
			});
		});

	});

	//close slideshow on esc and remove holder
	$(document).keyup(function(e) {
		if (e.keyCode == 27) { // esc
			$.endSlideshow();
		}
	});

});
