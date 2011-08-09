Collection={
	artists:[],
	loaded:false,
	loading:false,
	loadedListeners:[],
	load:function(ready){
		if(ready){
			Collection.loadedListeners.push(ready);
		}
		if(!Collection.loading){
			Collection.loading=true;
			$.ajax({
				url: OC.linkTo('media','ajax/api.php')+'?action=get_collection',
				dataType: 'json',
				success: function(collection){
					Collection.artists=collection;
					
					//set the album and artist fieds for the songs
					for(var i=0;i<collection.length;i++){
						var artist=collection[i];
						for(var j=0;j<artist.albums.length;j++){
							var album=artist.albums[j]
							for(var w=0;w<album.songs.length;w++){
								album.songs[w].album_name=album.album_name;
								album.songs[w].artist_name=artist.artist_name;
							}
						}
					}
					
					Collection.loaded=true;
					Collection.loading=false;
					for(var i=0;i<Collection.loadedListeners.length;i++){
						Collection.loadedListeners[i]();
					}
					if(collection.length==0){
						$('#scan input.start').val('Scan Collection');
						$('#plugins a[href="#collection"]').trigger('click');
					}
					
				}
			});
		}
	},
	display:function(){
		if(Collection.parent){
			Collection.parent.show();
		}
		if(!Collection.loaded){
			Collection.load(Collection.display)
		}else{
			if(Collection.parent){
				Collection.parent.find('tr:not(.template)').remove();
				var template=Collection.parent.find('tr.template');
				var lastArtist='';
				var lastAlbum='';
				$.each(Collection.artists,function(index,artist){
					$.each(artist.albums,function(index,album){
						$.each(album.songs,function(index,song){
							var tr=template.clone().removeClass('template');
							tr.find('td.title a').text(song.song_name);
							tr.find('td.title a').click(function(event){
								event.preventDefault();
								PlayList.add(song);
								PlayList.render();
							});
							if(artist.artist_name!=lastArtist){
								tr.find('td.artist a').click(function(event){
									event.preventDefault();
									PlayList.add(artist);
									PlayList.render();
								});
								tr.find('td.artist a').text(artist.artist_name);
								if(artist.albums.length>1){
									var expander=$('<a class="expander">&gt;</a>');
									expander.data('expanded',true);
									expander.click(function(event){
										var tr=$(this).parent().parent();
										if(expander.data('expanded')){
											Collection.hideArtist(tr.data('artist'));
										}else{
											Collection.showArtist(tr.data('artist'));
										}
									});
									tr.children('td.artist').append(expander);
								}
							}
							if(album.album_name!=lastAlbum){
								tr.find('td.album a').click(function(event){
									event.preventDefault();
									PlayList.add(album);
									PlayList.render();
								});
								tr.find('td.album a').text(album.album_name);
								if(album.songs.length>1){
									var expander=$('<a class="expander">&gt;</a>');
									expander.data('expanded',true);
									expander.click(function(event){
										var tr=$(this).parent().parent();
										if(expander.data('expanded')){
											Collection.hideAlbum(tr.data('album'));
										}else{
											Collection.showAlbum(tr.data('album'));
										}
									});
									tr.children('td.album').append(expander);
								}
							}
							tr.attr('data-artist',artist.artist_name);
							tr.attr('data-album',album.album_name);
							lastArtist=artist.artist_name;
							lastAlbum=album.album_name;
							
							Collection.parent.find('tbody').append(tr);
						});
						Collection.hideAlbum(artist.artist_name,album.album_name);
					});
					Collection.hideArtist(artist.artist_name);
				});
			}
		}
	},
	showArtist:function(artist){
		Collection.parent.find('tr[data-artist="'+artist+'"]').show();
		Collection.parent.find('tr[data-artist="'+artist+'"]').first().removeClass('collapsed');
		Collection.parent.find('tr[data-artist="'+artist+'"] a.expander').data('expanded',true);
		Collection.parent.find('tr[data-artist="'+artist+'"] a.expander').addClass('expanded');
		Collection.parent.find('tr[data-artist="'+artist+'"] a.expander').text('v');
	},
	hideArtist:function(artist){
		if(Collection.parent.find('tr[data-artist="'+artist+'"]').length>1){
			Collection.parent.find('tr[data-artist="'+artist+'"]').hide();
			Collection.parent.find('tr[data-artist="'+artist+'"]').first().show();
			Collection.parent.find('tr[data-artist="'+artist+'"]').first().addClass('collapsed');
			Collection.parent.find('tr[data-artist="'+artist+'"] a.expander').data('expanded',false);
			Collection.parent.find('tr[data-artist="'+artist+'"] a.expander').removeClass('expanded');
			Collection.parent.find('tr[data-artist="'+artist+'"] a.expander').text('>');
		}
	},
	showAlbum:function(artist,album){
		Collection.parent.find('tr[data-artist="'+artist+'"][data-album="'+album+'"]').show();
	},
	hideAlbum:function(artist,album){
		Collection.parent.find('tr[data-artist="'+artist+'"][data-album="'+album+'"]').hide();
		Collection.parent.find('tr[data-artist="'+artist+'"][data-album="'+album+'"]').last().show();
	},
	parent:null,
	hide:function(){
		if(Collection.parent){
			Collection.parent.hide();
		}
	},
	registerPlay:function(){
		var item=PlayList.items[PlayList.current];
		var song=Collection.findSong(item.artist,item.album,item.name);
		song.song_playcount++;
	},
	addButtons:function(parent){
		parent.children('button.add').click(function(){
			var type=$(this).parent().data('type');
			PlayList.add($(this).parent().data(type));
		});
		parent.children('button.play').click(function(){
			var type=$(this).parent().data('type');
			var oldSize=PlayList.items.length;
			PlayList.add($(this).parent().data(type));
			PlayList.play(oldSize);
		});
	},
	find:function(artistName,albumName,songName){
		if(songName){
			return Collection.findSong(artistName,albumName,songName);
		}else if(albumName){
			return Collection.findAlbum(artistName,albumName);
		}else{
			return Collection.findArtist(artistName);
		}
	},
	findArtist:function(name){
		for(var i=0;i<Collection.artists.length;i++){
			if(Collection.artists[i].artist_name==name){
				return Collection.artists[i];
			}
		}
	},
	findAlbum:function(artistName,albumName){
		var artist=Collection.findArtist(artistName);
		if(artist){
			for(var i=0;i<artist.albums.length;i++){
				if(artist.albums[i].album_name==albumName){
					return artist.albums[i];
				}
			}
		}
	},
	findSong:function(artistName,albumName,songName){
		var album=Collection.findAlbum(artistName,albumName);
		if(album){
			for(var i=0;i<album.songs.length;i++){
				if(album.songs[i].song_name==songName){
					return album.songs[i];
				}
			}
		}
	},
	addSong:function(song){
		var artist=false
		var album=false;
		for(var i=0;i<Collection.artists.length;i++){
			if(Collection.artists[i].artist_id==song.song_artist){
				artist=Collection.artists[i];
				for(var j=0;j<artist.albums.length;j++){
					if(artist.albums[j].album_id==song.song_album){
						album=artist.albums[j];
						break;
					}
				}
				break;
			}
		}
		if(!artist){
			artist={artist_id:song.song_artist,artist_name:song.artist,albums:[]};
			Collection.artists.push(artist);
			if(!Collection.parent || Collection.parent.is(":visible")){
				Collection.display();
			}
			
		}
		if(!album){
			album={album_id:song.song_album,album_name:song.album,album_artist:song.song_artist,songs:[]};
			artist.albums.push(album)
		}
		album.songs.push(song)
	}
}

$(document).ready(function(){
	Collection.parent=$('#collection');
	Collection.load();
	Collection.parent.hide();
	$('#scan input.start').click(function(){
		$('#scan input.start').hide();
		$('#scan input.stop').show();
		$('#scan input.stop').click(function(){
			Scanner.toggle();
		});
		Scanner.scanCollection();
	});
});
