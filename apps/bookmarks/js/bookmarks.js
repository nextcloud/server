var bookmarks_page = 0;
var bookmarks_loading = false;

var bookmarks_sorting = 'bookmarks_sorting_recent';

$(document).ready(function() {
	$('.bookmarks_addBtn').click(function(event){
		$('.bookmarks_add').slideToggle();
	});
	
	$('#bookmark_add_submit').click(addBookmark);
	$(window).scroll(updateOnBottom);
	
	$('#bookmark_add_url').focusout(getMetadata);
	$('.' + bookmarks_sorting).addClass('bookmarks_sorting_active');
	
	$('.bookmarks_sorting li').click(function(event){changeSorting(this)});
	
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
		data: 'tag=' + encodeURI($('#bookmarkFilterTag').val()) + '&page=' + bookmarks_page + '&sort=' + bookmarks_sorting,
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

function getMetadata() {
	var url = encodeEntities($('#bookmark_add_url').val())
	$.ajax({
		url: 'ajax/getMeta.php',
		data: 'url=' + encodeURIComponent(url),
		success: function(pageinfo){
			$('#bookmark_add_url').val(pageinfo.data.url);
			$('#bookmark_add_description').val(pageinfo.data.description);
			$('#bookmark_add_title').val(pageinfo.data.title);
		}
	});
}

function changeSorting(sortEl) {
	$('.' + bookmarks_sorting).removeClass('bookmarks_sorting_active');
	bookmarks_sorting = sortEl.className;
	$('.' + bookmarks_sorting).addClass('bookmarks_sorting_active');
	
	$('.bookmarks_list').empty();
	bookmarks_page = 0;
	bookmarks_loading = false;
	getBookmarks();
}

function addBookmark(event) {
	var url = encodeEntities($('#bookmark_add_url').val())
	var title = encodeEntities($('#bookmark_add_title').val())
	var description = encodeEntities($('#bookmark_add_description').val())
	var tags = encodeEntities($('#bookmark_add_tags').val())
	var taglist = tags.split(' ')
	var tagshtml = '';
	for ( var i=0, len=taglist.length; i<len; ++i ){
		tagshtml += '<a class="bookmark_tags" href="?tag=' + encodeURI(taglist[i]) + '">' + taglist[i] + '</a> ';
	}
	$.ajax({
		url: 'ajax/addBookmark.php',
		data: 'url=' + encodeURI(url) + '&title=' + encodeURI(title) + '&description=' + encodeURI(description) + '&tags=' + encodeURI(tags),
		success: function(data){ 
			$('.bookmarks_add').slideToggle(); 
			$('.bookmarks_add').children('p').children('.bookmarks_input').val(''); 
			$('.bookmarks_list').prepend(
			'<div class="bookmark_single">' +
				'<p class="bookmark_title"><a href="' + url + '" target="_new" class="bookmark_link">' + title + '</a></p>' +
				'<p class="bookmark_url">' + url + '</p>' +
				'<p class="bookmark_description">' + description + '</p>' +
				'<p>' + tagshtml + '</p>' +
				'<p class="bookmark_actions"><span class="bookmark_delete">Delete</span></p>' +
			'</div>'
			);
		}
	});
}

function delBookmark(event) {
	var record = $(this).parent().parent()
	$.ajax({
		url: 'ajax/delBookmark.php',
		data: 'url=' + encodeURI($(this).parent().parent().children('.bookmark_url:first').text()),
		success: function(data){ record.animate({ opacity: 'hide' }, 'fast'); }
	});
}

function updateBookmarksList(bookmark) {
	var tags = encodeEntities(bookmark.tags).split(' ');
	var taglist = '';
	for ( var i=0, len=tags.length; i<len; ++i ){
		taglist = taglist + '<a class="bookmark_tags" href="?tag=' + encodeURI(tags[i]) + '">' + tags[i] + '</a> ';
	}
	if(!hasProtocol(bookmark.url)) {
		bookmark.url = 'http://' + bookmark.url;
	}
	$('.bookmarks_list').append(
		'<div class="bookmark_single">' +
			'<p class="bookmark_title"><a href="' + encodeEntities(bookmark.url) + '" target="_new" class="bookmark_link">' + encodeEntities(bookmark.title) + '</a></p>' +
			'<p class="bookmark_url">' + encodeEntities(bookmark.url) + '</p>' +
			'<p class="bookmark_description">' + encodeEntities(bookmark.description) + '</p>' +
			'<p>' + taglist + '</p>' +
			'<p class="bookmark_actions"><span class="bookmark_delete">Delete</span></p>' +
		'</div>'
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
		data: 'url=' + encodeURI($(this).attr('href')),
	});	
}

function encodeEntities(s){
	try {
		return $('<div/>').text(s).html();
		
	} catch (ex) {
		return "";
	}
}

function hasProtocol(url) {
    var regexp = /(ftp|http|https|sftp)/;
    return regexp.test(url);
}
