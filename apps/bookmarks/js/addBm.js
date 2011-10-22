$(document).ready(function() {
	$('#bookmark_add_submit').click(addBookmark);
});

function addBookmark(event) {
	var url = $('#bookmark_add_url').val();
	var title = $('#bookmark_add_title').val();
	var tags = $('#bookmark_add_tags').val();
	$.ajax({
		url: 'ajax/addBookmark.php',
		data: 'url=' + encodeURI(url) + '&title=' + encodeURI(title) + '&tags=' + encodeURI(tags),
		success: function(data){ 
			location.href='index.php';
		}
	});
}