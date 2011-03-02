$(document).ready(function() {

    // Hides the user_menu div :
    $('#user_menu').hide();

    // Sets user_menu link behaviour :
    $('#user_menu_link').click(function() {
        $('#user').toggleClass('userLinkOn');
        $('#user_menu').slideToggle(250);
        return false;
    });
});
