$(document).ready(function() {

    // Hides the user_menu div :
    $('#user_menu').hide();

    // Sets user_menu link behaviour :
    $('#user_menu_link').click(function() {
        $('#user').toggleClass('userLinkOn');
        $('#user_menu').slideToggle(250);
        return false;
    });
	
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
