$(document).ready(function(){
    	newSiteHtml = '<li><input type="text" class="site_name" name="site_name[]" value="" placeholder="Name" />\n\
    <input type="text" name="site_url[]" class="site_url" value=""  placeholder="URL" />\n\
<img class="svg action delete_button" src="'+OC.imagePath("core", "actions/delete") +'" title="Remove site" /></li>';

	// Handler functions
	function addSiteEventHandler(event) {
		event.preventDefault();
		
		saveSites();
	}

	function deleteButtonEventHandler(event) {
		event.preventDefault();

		$(this).tipsy('hide');
		$(this).parent().remove();

		saveSites();
	}

	function saveSites() {
		var post = $('#external').serialize();
		$.post( OC.filePath('external','ajax','setsites.php') , post, function(data) {
			// OC.msg.finishedSaving('#site_name .msg', data); 
		});
	}

	function showDeleteButton(event) {
		$(this).find('img.delete_button').fadeIn(100);
	} 

	function hideDeleteButton(event) {
		$(this).find('img.delete_button').fadeOut(100);
	}

	// Initialize events
	$('input[name^=site_]').change(addSiteEventHandler);
	$('img.delete_button').click(deleteButtonEventHandler);
	$('img.delete_button').tipsy();

	$('#external li').hover(showDeleteButton, hideDeleteButton);

	$('#add_external_site').click(function(event) {
		event.preventDefault();
		$('#external ul').append(newSiteHtml);

		$('input.site_url:last').prev('input.site_name').andSelf().change(addSiteEventHandler);
		$('img.delete_button').click(deleteButtonEventHandler);
		$('img.delete_button:last').tipsy();
		$('#external li:last').hover(showDeleteButton, hideDeleteButton);
		
	});

});


