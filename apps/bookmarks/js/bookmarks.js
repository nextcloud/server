var bookmarks_page = 0;
var bookmarks_loading = false;

$(document).ready(function() {
	$('.bookmarks_addBtn').click(function(event){
		$('.bookmarks_add').slideToggle();
	});
	
	$('#bookmark_add_submit').click(addBookmark);
	$(window).scroll(updateOnBottom);
	
	$('.bookmarks_list').empty();
	getBookmarks();
});

function getBookmarks() {
	if(bookmarks_loading) {
		//have patience :)
		return;
	}
	$.ajax({
		url: 'ajax/updateList.php',
		data: "tag=" + encodeURI($('#bookmarkFilterTag').val()) + "&page=" + bookmarks_page,
		success: function(bookmarks){
			bookmarks_page += 1;
			$('.bookmark_link').unbind('click', recordClick);
			$('.bookmark_delete').unbind('click', delBookmark);
	
			for(var i in bookmarks.data) {
				updateBookmarksList(bookmarks.data[i]);
			}
			$('.bookmark_link').click(recordClick);
			$('.bookmark_delete').click(delBookmark);
			bookmarks_loading = false;
		}
	});	
}

function addBookmark(event) {
	var url = $('#bookmark_add_url').val()
	var title = $('#bookmark_add_title').val()
	var description = $('#bookmark_add_description').val()
	var tags = $('#bookmark_add_tags').val()
	$.ajax({
		url: 'ajax/addBookmark.php',
		data: "url=" + encodeURI(url) + "&title=" + encodeURI(title) + "&description=" + encodeURI(description) + "&tags=" + encodeURI(tags),
		success: function(data){ 
			$('.bookmarks_add').slideToggle(); 
			$('.bookmarks_add').children('p').children('.bookmarks_input').val(''); 
			$('.bookmarks_list').prepend(
			"<div class=\"bookmark_single\">" +
				"<p class=\"bookmark_title\"><a href=\"" + url + "\" target=\"_new\" class=\"bookmark_link\">" + title + "</a></p>" +
				"<p class=\"bookmark_url\">" + url + "</p>" +
				"<p class=\"bookmark_description\">" + description + "</p>" +
				"<p>" + tags + "</p>" +
				"<p class=\"bookmark_actions\"><span class=\"bookmark_delete\">Delete</span></p>" +
			"</div>"
			);
		}
	});
}

function delBookmark(event) {
	var record = $(this).parent().parent()
	$.ajax({
		url: 'ajax/delBookmark.php',
		data: "url=" + encodeURI($(this).parent().parent().children('.bookmark_url:first').text()),
		success: function(data){ record.animate({ opacity: "hide" }, "fast"); }
	});
}

function updateBookmarksList(bookmark) {
	var tags = encodeEntities(bookmark.tags).split(" ");
	var taglist = "";
	for ( var i=0, len=tags.length; i<len; ++i ){
		taglist = taglist + "<a class=\"bookmark_tags\" href=\"?tag=" + encodeURI(tags[i]) + "\">" + tags[i] + "</a> ";
	}
	$('.bookmarks_list').append(
		"<div class=\"bookmark_single\">" +
			"<p class=\"bookmark_title\"><a href=\"" + encodeEntities(bookmark.url) + "\" target=\"_new\" class=\"bookmark_link\">" + encodeEntities(bookmark.title) + "</a></p>" +
			"<p class=\"bookmark_url\">" + encodeEntities(bookmark.url) + "</p>" +
			"<p class=\"bookmark_description\">" + encodeEntities(bookmark.description) + "</p>" +
			"<p>" + taglist + "</p>" +
			"<p class=\"bookmark_actions\"><span class=\"bookmark_delete\">Delete</span></p>" +
		"</div>"
	);
}

function updateOnBottom() {
	//check wether user is on bottom of the page
	if ($('body').height() <= ($(window).height() + $(window).scrollTop())) {
		getBookmarks();
	}
}

function recordClick(event) {
	$.ajax({
		url: 'ajax/recordClick.php',
		data: "url=" + encodeURI($(this).attr('href')),
	});	
}

function encodeEntities(s){
	try {
		return $("<div/>").text(s).html();
		
	} catch (ex) {
		return "";
	}
}
