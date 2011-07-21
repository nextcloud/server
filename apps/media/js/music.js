var audioPlaylist;
var URLBASE='ajax/api.php?action=play&path=';

$(document).ready(function() {
	if(typeof FileActions!=='undefined'){
		URLBASE='../apps/media/ajax/api.php?action=play&path=';
		var playerLoaded=false;
		function playFile(filename){
			audioPlaylist.playlist=[];
			audioPlaylist.addToPlaylist({
				song_name:filename,
				song_path:$('#dir').val()+'/'+filename
			},true);
			audioPlaylist.playlistChange(audioPlaylist.playlist.length-1);
		}
		function playAudio(filename){
			if(!playerLoaded){
				var parent=$('body').append('<div id="media_container"/>');
				$('#media_container').load('../apps/media/templates/music.php',function(){
					playerLoaded=true;
					//remove playlist and collection view
					$('#jp_playlist_1').remove();
					$('collection').remove();
					//init the audio player
					audioPlaylist =initPlayList(false,false,function(){
						//play the file
						playFile(filename);
					});
				});
			}else{
				playFile(filename);
			}
		}
		FileActions.register('audio','Play',playAudio);
		FileActions.register('application/ogg','Play',playAudio);
		FileActions.setDefault('audio','Play');
		FileActions.setDefault('application/ogg','Play');
	}
	Playlist = function(instance, playlist, options) {
		var self = this;
		
		this.instance = instance; // String: To associate specific HTML with this playlist
		this.playlist = playlist; // Array of Objects: The playlist
		this.options = options; // Object: The jPlayer constructor options for this playlist
		
		this.current = -1;
		
		this.cssId = {
			jPlayer: "jplayer_",
			interface: "jp_interface_",
			playlist: "jp_playlist_"
		};
		this.cssSelector = {};
		
		$.each(this.cssId, function(entity, id) {
			self.cssSelector[entity] = "#" + id + self.instance;
		});
		
		if(!this.options.cssSelectorAncestor) {
			this.options.cssSelectorAncestor = this.cssSelector.interface;
		}
		
		$(this.cssSelector.jPlayer).jPlayer(this.options);
		
		$(this.cssSelector.interface + " .jp-previous").click(function() {
			self.playlistPrev();
			$(this).blur();
			return false;
		});
		
		$(this.cssSelector.interface + " .jp-next").click(function() {
			self.playlistNext();
			$(this).blur();
			return false;
		});
	};
	
	Playlist.prototype = {
		displayPlaylist: function() {
			var self = this;
			$(this.cssSelector.playlist + " ul").empty();
			for (i=0; i < this.playlist.length; i++) {
				var listItem = (i === this.playlist.length-1) ? "<li class='jp-playlist-last'>" : "<li>";
				listItem += "<a href='#' id='" + this.cssId.playlist + this.instance + "_item_" + i +"' tabindex='1'>"+ this.playlist[i].name +"</a>";
				
				// Create links to free media
				if(this.playlist[i].free) {
					var first = true;
					listItem += "<div class='jp-free-media'>(";
					$.each(this.playlist[i], function(property,value) {
						if($.jPlayer.prototype.format[property]) { // Check property is a media format.
							if(first) {
								first = false;
							} else {
								listItem += " | ";
							}
							listItem += "<a id='" + self.cssId.playlist + self.instance + "_item_" + i + "_" + property + "' href='" + value + "' tabindex='1'>" + property + "</a>";
						}
					});
					listItem += ")</span>";
				}
				listItem += "<button class='right prettybutton remove'>Remove</button>";
				
				listItem += "</li>";
				
				// Associate playlist items with their media
				$(this.cssSelector.playlist + " ul").append(listItem);
				$(this.cssSelector.playlist + "_item_" + i).data("index", i).click(function() {
					var index = $(this).data("index");
					if(self.current !== index) {
						self.playlistChange(index);
					} else {
						$(self.cssSelector.jPlayer).jPlayer("play");
					}
					$(this).blur();
					return false;
				});
				$(this.cssSelector.playlist + "_item_" + i).parent().children('button').data("index", i).click(function() {
					var index = $(this).data("index");
					self.removeFromPlaylist(index);
				});
				
				// Disable free media links to force access via right click
				if(this.playlist[i].free) {
					$.each(this.playlist[i], function(property,value) {
						if($.jPlayer.prototype.format[property]) { // Check property is a media format.
							$(self.cssSelector.playlist + "_item_" + i + "_" + property).data("index", i).click(function() {
								var index = $(this).data("index");
								$(self.cssSelector.playlist + "_item_" + index).click();
								$(this).blur();
								return false;
							});
						}
					});
				}
			}
		},
		playlistInit: function(autoplay) {
			if(autoplay) {
				this.playlistChange(this.current);
			} else {
				this.playlistConfig(this.current);
			}
		},
		playlistConfig: function(index,play) {
			$(this.cssSelector.playlist + "_item_" + this.current).removeClass("jp-playlist-current").parent().removeClass("jp-playlist-current");
			$(this.cssSelector.playlist + "_item_" + index).addClass("jp-playlist-current").parent().addClass("jp-playlist-current");
			this.current = index;
			var that=this;
			if(this.playlist[this.current]){
				if($(this.cssSelector.jPlayer).data('jPlayer').options.supplied!=this.playlist[this.current].type){//the the audio type changes we need to reinitialize jplayer
					$(this.cssSelector.jPlayer).jPlayer("destroy");
					$(this.cssSelector.jPlayer).jPlayer({
						ended:this.options.ended,
						play:this.options.play,
						supplied:this.playlist[this.current].type,
						ready:function(){
							that.playlistConfig(index);
							if(play){
								$(that.cssSelector.jPlayer).jPlayer("play");
							}
						}
					});
				}else{
					$(this.cssSelector.jPlayer).jPlayer("setMedia", this.playlist[this.current]);
				}
			}
		},
		playlistChange: function(index) {
			this.playlistConfig(index,true);
			$(this.cssSelector.jPlayer).jPlayer("play");
		},
		playlistNext: function() {
			var index = (this.current + 1 < this.playlist.length) ? this.current + 1 : 0;
			this.playlistChange(index);
		},
		playlistPrev: function() {
			var index = (this.current - 1 >= 0) ? this.current - 1 : this.playlist.length - 1;
			this.playlistChange(index);
		},
		removeFromPlaylist: function(index){
			this.playlist.splice(index,1);
			this.displayPlaylist();
			if(index==this.current){
				this.playlistConfig((index<this.playlist.length)?index:0);
			}else{
				$(this.cssSelector.playlist + "_item_" + this.current).addClass("jp-playlist-current").parent().addClass("jp-playlist-current");
			}
		},
		addToPlaylist : function(stuff,dontRedraw){
			var self=this;
			if(!stuff){
				return;
			}
			if(stuff.artist_name){
				$.each(stuff.albums,function(index,album){
					self.addToPlaylist(album,true);
				});
			}
			if(stuff.album_name){
				$.each(stuff.songs,function(index,song){
					self.addToPlaylist(song,true);
				});
			}
			if(stuff.song_name){
				var extention=stuff.song_path.split('.').pop();
				var type=musicTypeFromExtention(extention);
				var item={name:stuff.song_name,type:type};
				item[type]=URLBASE+stuff.song_path;
				this.playlist.push(item);
			}
			if(!dontRedraw){
				this.displayPlaylist();
			}
		}
	};

	if($('#jp-audio')){//only do this when we're actually in the media player
		//load the collection
		$.ajax({
			url: 'ajax/api.php?action=get_collection',
			dataType: 'json',
			success: function(collection){
				var playlist=[];
				var types=[];
				for(var i=0;i<collection.length;i++){
					var artist=collection[i];
					for(var j=0;j<artist.albums.length;j++){
						var album=artist.albums[j];
						for(var n=0;n<album.songs.length;n++){
							var song=album.songs[n];
							var extention=song.song_path.split('.').pop();
							var type=musicTypeFromExtention(extention);
							if(types.indexOf(type)==-1){
								types.push(type);
							}
						}
					}
				}
				displayCollection(collection);
				audioPlaylist =initPlayList(true,true);
			}
		});
	}
});

function initPlayList(display,enableAutoPlay,ready){
	return new Playlist('1', [],{
		ready: function() {
			if(display){
				audioPlaylist.displayPlaylist();
			}
			if(enableAutoPlay){
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
					audioPlaylist.addToPlaylist(play);
					audioPlaylist.playlistInit(true);
				}else{
					audioPlaylist.playlistInit(false); // Parameter is a boolean for autoplay.
				}
			}else{
				audioPlaylist.playlistInit(false);
			}
			if(ready){
				ready();
			}
		},
		ended: function() {
			audioPlaylist.playlistNext();
		},
		play: function() {
			$(this).jPlayer("pauseOthers");
		},
	});
}

function musicTypeFromExtention(extention){
	if(extention=='ogg'){
		return 'oga'
	}
	//TODO check for more specific cases
	return extention;
}

// indexOf implemententation for browsers that don't support it
if (!Array.prototype.indexOf){
	Array.prototype.indexOf = function(elt /*, from*/)	{
		var len = this.length;
		
		var from = Number(arguments[1]) || 0;
		from = (from < 0)
		? Math.ceil(from)
		: Math.floor(from);
		if (from < 0)
			from += len;
		
		for (; from < len; from++)
		{
			if (from in this &&
				this[from] === elt)
				return from;
		}
		return -1;
	};
}

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
		audioPlaylist.addToPlaylist($(this).parent().data('stuff'));
		return false;
	})
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