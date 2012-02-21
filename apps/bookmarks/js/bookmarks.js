var bookmarks_page = 0;
var bookmarks_loading = false;

var bookmarks_sorting = 'bookmarks_sorting_recent';

$(document).ready(function() {
	$('.bookmarks_addBtn').click(function(event){
		$('.bookmarks_add').slideToggle();
	});
	
	$('#bookmark_add_submit').click(addOrEditBookmark);
	$(window).scroll(updateOnBottom);
	
	$('#bookmark_add_url').focusout(getMetadata);
	
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
			$('.bookmark_edit').unbind('click', showBookmark);
	
			for(var i in bookmarks.data) {
				updateBookmarksList(bookmarks.data[i]);
				$("#firstrun").hide();
			}

			$('.bookmark_link').click(recordClick);
			$('.bookmark_delete').click(delBookmark);
			$('.bookmark_edit').click(showBookmark);
			
			bookmarks_loading = false;
		}
	});	
}

function getMetadata() {
	var url = encodeEntities($('#bookmark_add_url').val());
	$('.loading_meta').css('display','inline');
	$.ajax({
		url: 'ajax/getMeta.php',
		data: 'url=' + encodeURIComponent(url),
		success: function(pageinfo){
			$('#bookmark_add_url').val(pageinfo.data.url);
			$('#bookmark_add_title').val(pageinfo.data.title);
			$('.loading_meta').css('display','none');
		}
	});
}

// function addBookmark() {
// Instead of creating editBookmark() function, Converted the one above to
// addOrEditBookmark() to make .js file more compact.

function addOrEditBookmark(event) {
	var id = $('#bookmark_add_id').val();
	var url = encodeEntities($('#bookmark_add_url').val());
	var title = encodeEntities($('#bookmark_add_title').val());
	var tags = encodeEntities($('#bookmark_add_tags').val());
	var taglist = tags.split(' ');
	var tagshtml = '';
	$("#firstrun").hide();

	for ( var i=0, len=taglist.length; i<len; ++i ){
		tagshtml += '<a class="bookmark_tag" href="?tag=' + encodeURI(taglist[i]) + '">' + taglist[i] + '</a> ';
	}
	
	if (id == 0) {
		$.ajax({
			url: 'ajax/addBookmark.php',
			data: 'url=' + encodeURI(url) + '&title=' + encodeURI(title) + '&tags=' + encodeURI(tags),
			success: function(response){ 
				var bookmark_id = response.data;
				$('.bookmarks_add').slideToggle(); 
				$('.bookmarks_add').children('p').children('.bookmarks_input').val(''); 
				$('.bookmarks_list').prepend(
				'<div class="bookmark_single" data-id="' + bookmark_id + '" >' +
					'<p class="bookmark_actions">' +
						'<span class="bookmark_delete">' +
							'<img class="svg" src="'+OC.imagePath('core', 'actions/delete')+'" title="Delete">' +
						'</span>&nbsp;' +
						'<span class="bookmark_edit">' +
							'<img class="svg" src="'+OC.imagePath('core', 'actions/rename')+'" title="Edit">' +
						'</span>' +
					'</p>' +
					'<p class="bookmark_title"><a href="' + url + '" target="_blank" class="bookmark_link">' + title + '</a></p>' +
					'<p class="bookmark_tags">' + tagshtml + '</p>' +
					'<p class="bookmark_url">' + url + '</p>' +
				'</div>'
				);
			}
		});
	}
	else {
		$.ajax({
			url: 'ajax/editBookmark.php',
			data: 'id=' + id + '&url=' + encodeURI(url) + '&title=' + encodeURI(title) + '&tags=' + encodeURI(tags),
			success: function(){ 
				$('.bookmarks_add').slideToggle(); 
				$('.bookmarks_add').children('p').children('.bookmarks_input').val(''); 
				$('#bookmark_add_id').val('0');
				
				var record = $('.bookmark_single[data-id = "' + id + '"]');
				record.children('.bookmark_url:first').text(url);
				
				var record_title = record.children('.bookmark_title:first').children('a:first');
				record_title.attr('href', url);
				record_title.text(title);
				
				record.children('.bookmark_tags:first').html(tagshtml);
			}
		});
	}
	
}

function delBookmark(event) {
	var record = $(this).parent().parent();
	$.ajax({
		url: 'ajax/delBookmark.php',
		data: 'url=' + encodeURI($(this).parent().parent().children('.bookmark_url:first').text()),
		success: function(data){ record.animate({ opacity: 'hide' }, 'fast'); }
	});
}

function showBookmark(event) {
	var record = $(this).parent().parent();
	$('#bookmark_add_id').val(record.attr('data-id'));
	$('#bookmark_add_url').val(record.children('.bookmark_url:first').text());
	$('#bookmark_add_title').val(record.children('.bookmark_title:first').text());
	$('#bookmark_add_tags').val(record.children('.bookmark_tags:first').text());
	
	if ($('.bookmarks_add').css('display') == 'none') {
		$('.bookmarks_add').slideToggle();
	}
	$('html, body').animate({
      scrollTop: ($('.bookmarks_menu'))?$('.bookmarks_menu').offset().top:0
		}, 500);

}

function updateBookmarksList(bookmark) {
	var tags = encodeEntities(bookmark.tags).split(' ');
	var taglist = '';
	for ( var i=0, len=tags.length; i<len; ++i ){
		if(tags[i] != '')
			taglist = taglist + '<a class="bookmark_tag" href="?tag=' + encodeURI(tags[i]) + '">' + tags[i] + '</a> ';
	}
	if(!hasProtocol(bookmark.url)) {
		bookmark.url = 'http://' + bookmark.url;
	}
	$('.bookmarks_list').append(
		'<div class="bookmark_single" data-id="' + bookmark.id +'" >' +
			'<p class="bookmark_actions">' +
				'<span class="bookmark_delete">' +
					'<img class="svg" src="'+OC.imagePath('core', 'actions/delete')+'" title="Delete">' +
				'</span>&nbsp;' +
				'<span class="bookmark_edit">' +
					'<img class="svg" src="'+OC.imagePath('core', 'actions/rename')+'" title="Edit">' +
				'</span>' +
			'</p>' +
			'<p class="bookmark_title">'+
				'<a href="' + encodeEntities(bookmark.url) + '" target="_blank" class="bookmark_link">' + encodeEntities(bookmark.title) + '</a>' +
			'</p>' +
			'<p class="bookmark_url">' + encodeEntities(bookmark.url) + '</p>' +
		'</div>'
	);
	if(taglist != '') {
		$('div[data-id="'+ bookmark.id +'"]').append('<p class="bookmark_tags">' + taglist + '</p>');
	}
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
