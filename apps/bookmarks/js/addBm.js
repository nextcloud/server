$(document).ready(function() {
	$('#bookmark_add_submit').click(addBookmark);
});

function addBookmark(event) {
	var url = $('#bookmark_add_url').val();
	var tags = $('#bookmark_add_tags').val();
	$.ajax({
		type: 'POST',
		url: 'ajax/addBookmark.php',
		data: 'url=' + encodeURI(url) + '&tags=' + encodeURI(tags),
		success: function(data){ 
			window.close();
		}
	});
}