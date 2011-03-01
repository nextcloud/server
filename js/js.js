$(document).ready(function() {

    // Hides the user_menu div :
    $('#user_menu').hide();

    // Sets user_menu link behaviour :
    $('#user_menu_link').click(function() {
        $('#user').toggleClass('userLinkOn');
        $('#user_menu').slideToggle(250);
        return false;
    });

    // Sets browser table behaviour :
    $('.browser tr').hover(
        function() {
            $(this).addClass('mouseOver');
        },
        function() {
            $(this).removeClass('mouseOver');
        }
    );

    // Sets logs table behaviour :
    $('.logs tr').hover(
        function() {
            $(this).addClass('mouseOver');
        },
        function() {
            $(this).removeClass('mouseOver');
        }
    );

    // Sets the file-action buttons behaviour :
    $('td.fileaction a').click(function() {
        $(this).parent().append($('#file_menu'));
        $('#file_menu').slideToggle(250);
        return false;
    });

    // Sets the select_all checkbox behaviour :
    $('#select_all').click(function() {

        if($(this).attr('checked'))
            // Check all
            $('.browser input:checkbox').attr('checked', true);
        else
            // Uncheck all
            $('.browser input:checkbox').attr('checked', false);
    });
});
