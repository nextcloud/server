Collection={
	artists:[],
	albums:[],
	songs:[],
	artistsById:{},
	albumsById:{},
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
				success: function(data){
					//normalize the data
					for(var i=0;i<data.artists.length;i++){
						var artist=data.artists[i];
						var artistData={name:artist.artist_name,songs:[],albums:[]};
						Collection.artistsById[artist.artist_id]=artistData;
						Collection.artists.push(artistData);
					}
					for(var i=0;i<data.albums.length;i++){
						var album=data.albums[i];
						var artistName=Collection.artistsById[album.album_artist].name;
						var albumData={name:album.album_name,artist:artistName,songs:[]};
						Collection.albumsById[album.album_id]=albumData;
						Collection.albums.push(albumData);
						Collection.artistsById[album.album_artist].albums.push(albumData);
					}
					for(var i=0;i<data.songs.length;i++){
						var song=data.songs[i];
						if(Collection.artistsById[song.song_artist] && Collection.albumsById[song.song_album]){
							var songData={
								name:song.song_name,
								artist:Collection.artistsById[song.song_artist].name,
								album:Collection.albumsById[song.song_album].name,
								lastPlayed:song.song_lastplayed,
								length:song.song_length,
								path:song.song_path,
								playCount:song.song_playcount,
							};
							Collection.songs.push(songData);
							Collection.artistsById[song.song_artist].songs.push(songData);
							Collection.albumsById[song.song_album].songs.push(songData);
						}
					}
					
					Collection.loaded=true;
					Collection.loading=false;
					for(var i=0;i<Collection.loadedListeners.length;i++){
						Collection.loadedListeners[i]();
					}
					if(collection.length==0){
						$('#scan input.start').val(t('media','Scan Collection'));
						$('#scan input.start').click();
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
				$.each(Collection.artists,function(i,artist){
					if(artist.name && artist.songs.length>0){
						var tr=template.clone().removeClass('template');
						tr.find('td.title a').text(artist.songs.length+' '+t('media','songs'));
						tr.find('td.album a').text(artist.albums.length+' '+t('media','albums'));
						tr.find('td.artist a').text(artist.name);
						tr.data('artistData',artist);
						tr.find('td.artist a').click(function(event){
							event.preventDefault();
							PlayList.add(artist,true);
							PlayList.play(0);
							Collection.parent.find('tr').removeClass('active');
							$('tr[data-artist="'+artist.name+'"]').addClass('active');
						});
						var expander=$('<a class="expander">&gt;</a>');
						expander.data('expanded',false);
						expander.click(function(event){
							var tr=$(this).parent().parent();
							if(expander.data('expanded')){
								Collection.hideArtist(tr.data('artist'));
							}else{
								Collection.showArtist(tr.data('artist'));
							}
						});
						tr.children('td.artist').append(expander);
						tr.attr('data-artist',artist.name);
						Collection.parent.find('tbody').append(tr);
					}
				});
			}
		}
	},
	showArtist:function(artist){
		var tr=Collection.parent.find('tr[data-artist="'+artist+'"]');
		var nextRow=tr.next();
		var artist=tr.data('artistData');
		var first=true;
		$.each(artist.albums,function(foo,album){
			$.each(album.songs,function(i,song){
				if(first){
					newRow=tr;
				}else{
					var newRow=tr.clone();
				}
				if(i==0){
					newRow.find('td.album a').text(album.name);
					newRow.find('td.album a').click(function(event){
						event.preventDefault();
						PlayList.add(album,true);
						PlayList.play(0);
						Collection.parent.find('tr').removeClass('active');
						$('tr[data-album="'+album.name+'"]').addClass('active');
					});
				}else{
					newRow.find('.expander').remove();
					newRow.find('td.album a').text('');
				}
				newRow.find('td.title a').text(song.name);
				newRow.find('td.title a').click(function(event){
					event.preventDefault();
					PlayList.add(song,true);
					PlayList.play(0);
					Collection.parent.find('tr').removeClass('active');
					$('tr[data-title="'+song.name+'"]').addClass('active');
				});
				newRow.attr('data-album',album.name);
				newRow.attr('data-title',song.name);
				newRow.attr('data-artist',artist.name);
				if(!first){
					nextRow.before(newRow);
				}
				first=false;
			});
		});
		tr.removeClass('collapsed');
		tr.find('a.expander').data('expanded',true);
		tr.find('a.expander').addClass('expanded');
		tr.find('a.expander').text('v');
	},
	hideArtist:function(artist){
		var tr=Collection.parent.find('tr[data-artist="'+artist+'"]');
		if(tr.length>1){
			var artist=tr.first().data('artistData');
			tr.first().find('td.album a').text(artist.albums.length+' '+t('media','albums'));
			tr.first().find('td.title a').text(artist.songs.length+' '+t('media','songs'));
			tr.first().find('td.album a').unbind('click');
			tr.first().find('td.title a').unbind('click');
			tr.each(function(i,row){
				if(i>0){
					$(row).remove();
				}
			});
			tr.find('a.expander').data('expanded',false);
			tr.find('a.expander').removeClass('expanded');
			tr.find('a.expander').text('>');
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
	registerPlay:function(item){
		if(item){
			var song=Collection.findSong(item.artist,item.album,item.name);
			song.song_playcount++;
		}
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
