$(document).ready(function() {	
	//hide the advanced config
	$('#advanced_options').hide();
	$('#use_mysql').hide();
	
	// Sets advanced_options link behaviour :
	$('#advanced_options_link').click(function() {
		$('#advanced').toggleClass('userLinkOn');
		$('#advanced_options').slideToggle(250);
		return false;
	});
	
	$('#mysql').click(function() {
		$('#use_mysql').slideDown(250);
	});
	$('#sqlite').click(function() {
		$('#use_mysql').slideUp(250);
	});
});
