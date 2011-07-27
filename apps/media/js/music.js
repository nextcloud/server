$(document).ready(function(){
	//load the collection
	$.ajax({
		url: OC.linkTo('media','ajax/api.php')+'?action=get_collection',
		dataType: 'json',
		success: function(collection){
			displayCollection(collection);
		}
	});
});

function displayCollection(collection){
	$('#collection').data('collection',collection);
	$.each(collection,function(index,artist){
		var artistNode=$('<li class="artist">'+artist.artist_name+'<button class="add">Add</button><ul/></li>');
		artistNode.data('name',artist.artist_name);
		artistNode.data('stuff',artist);
		$('#collection>ul').append(artistNode);
		$.each(artist.albums,function(index,album){
			var albumNode=$('<li class="album">'+album.album_name+'<button class="add">Add</button><ul/></li>');
			albumNode.data('name',album.album_name);
			albumNode.data('stuff',album);
			artistNode.children('ul').append(albumNode);
			$.each(album.songs,function(index,song){
				var songNode=$('<li class="song">'+song.song_name+'<button class="add">Add</button></li>');
				song.artist_name=artist.artist_name;
				song.album_name=album.album_name;
				songNode.data('name',song.song_name);
				songNode.data('stuff',song);
				albumNode.children('ul').append(songNode);
			});
		});
	});
	$('li.album').hide();
	$('li.song').hide();
	$('li.artist').click(function(){
		$(this).children().children().slideToggle();
		return false;
	});
	$('li.album').click(function(){
		$(this).children().children().slideToggle();
		return false;
	});
	$('li.song').click(function(){
		return false;
	});
	$('li>button.add').click(function(){
		PlayList.add($(this).parent().data('stuff'));
		PlayList.render($('#playlist'));
		return false;
	});
	if(window.location.href.indexOf('#')>-1){//autoplay passed arist/album/song
		var vars=getUrlVars();
		var play;
		if(vars['artist']){
			$.each(collection,function(index,artist){
				if(artist.artist_name==vars['artist']){
					play=artist;
					if(vars['album']){
						$.each(artist.albums,function(index,album){
							if(album.album_name==vars['album']){
								play=album;
								if(vars['song']){
									$.each(album.songs,function(index,song){
										if(song.song_name==vars['song']){
											play=song;
										}
									});
								}
							}
						});
					}
				}
			});
		}
		PlayList.add(play);
		PlayList.play();
	}else{
		PlayList.init();
	}
	
}

function getUrlVars(){
	var vars = [], hash;
	var hashes = window.location.href.slice(window.location.href.indexOf('#') + 1).split('&');
	for(var i = 0; i < hashes.length; i++)
	{
		hash = hashes[i].split('=');
		vars.push(hash[0]);
		vars[hash[0]] = decodeURIComponent(hash[1]).replace(/\+/g,' ');
	}
	return vars;
}

function musicTypeFromFile(file){
	var extention=file.substr(file.indexOf('.')+1);
	if(extention=='ogg'){
		return 'oga'
	}
	//TODO check for more specific cases
	return extention;
}