var PlayList={
	urlBase:OC.linkTo('media','ajax/api.php')+'?action=play&path=',
	current:-1,
	items:[],
	player:null,
	volume:0.8,
	active:false,
	next:function(){
		var next=PlayList.current+1;
		if(next>=PlayList.items.length){
			next=0;
		}
		PlayList.play(next);
		PlayList.render();
	},
	previous:function(){
		var next=PlayList.current-1;
		if(next<0){
			next=PlayList.items.length-1;
		}
		PlayList.play(next);
		PlayList.render();
	},
	play:function(index,time,ready){
		if(index==null){
			index=PlayList.current;
		}
		if(index>-1 && index<PlayList.items.length){
			PlayList.current=index;
			if(PlayList.player){
				if(PlayList.player.data('jPlayer').options.supplied!=PlayList.items[index].type){//the the audio type changes we need to reinitialize jplayer
					PlayList.player.jPlayer("destroy");
					PlayList.init(PlayList.items[index].type,function(){PlayList.play(null,time,eady)});
				}else{
					PlayList.player.jPlayer("setMedia", PlayList.items[PlayList.current]);
					PlayList.items[index].playcount++;
					PlayList.player.jPlayer("play",time);
					if(index>0){
						var previous=index-1;
					}else{
						var previous=PlayList.items.length-1;
					}
					if(index+1<PlayList.items.length){
						var next=index+1;
					}else{
						var next=0;
					}
					$('.jp-next').attr('title',PlayList.items[next].name);
					$('.jp-previous').attr('title',PlayList.items[previous].name);
					if (typeof Collection !== 'undefined') {
						Collection.registerPlay();
					}
					if(ready){
						ready();
					}
				}
			}else{
				PlayList.init(PlayList.items[index].type,PlayList.play);
			}
		}
	},
	init:function(type,ready){
		if(!PlayList.player){
			$(".jp-previous").click(function() {
				PlayList.previous();
				$(this).blur();
				PlayList.render();
				return false;
			});
			$(".jp-next").click(function() {
				PlayList.next();
				$(this).blur();
				PlayList.render();
				return false;
			});
			PlayList.player=$('#jp-interface div.player');
		}
		$(PlayList.player).jPlayer({
			ended:PlayList.next,
			pause:function(){
				localStorage.setItem(oc_current_user+'oc_playlist_playing','false');
			},
			play:function(){
				localStorage.setItem(oc_current_user+'oc_playlist_playing','true');
			},
			supplied:type,
			ready:function(){
				PlayList.load();
				if(ready){
					ready();
				}
			},
			volume:PlayList.volume,
			cssSelectorAncestor:'#jp-interface',
			swfPath:OC.linkTo('media','js'),
		});
	},
	add:function(song){
		if(!song){
			return;
		}
		if(song.substr){//we are passed a string, asume it's a url to a song
			PlayList.addFile(song);
		}
		if(song.albums){//a artist object was passed, add all albums inside it
			$.each(song.albums,function(index,album){
				PlayList.add(album);
			});
		}
		if(song.songs){//a album object was passed, add all songs inside it
			$.each(song.songs,function(index,song){
				PlayList.add(song);
			});
		}
		if(song.song_name){
			var type=musicTypeFromFile(song.song_path);
			var item={name:song.song_name,type:type,artist:song.artist_name,album:song.album_name,length:song.song_length,playcount:song.song_playcount};
			item[type]=PlayList.urlBase+encodeURIComponent(song.song_path);
			PlayList.items.push(item);
		}
	},
	addFile:function(path){
		var type=musicTypeFromFile(path);
		var item={name:'unknown',artist:'unknown',album:'unknwon',type:type};
		$.getJSON(OC.filePath('media','ajax','api.php')+'?action=get_path_info&path='+encodeURIComponent(path),function(song){
			item.name=song.song_name;
			item.artist=song.artist;
			item.album=song.album;
		});
		item[type]=PlayList.urlBase+encodeURIComponent(path);
		PlayList.items.push(item);
	},
	remove:function(index){
		PlayList.items.splice(index,1);
		PlayList.render();
	},
	render:function(){},
	playing:function(){
		if(!PlayList.player){
			return false;
		}else{
			return !PlayList.player.data("jPlayer").status.paused;
		}
	},
	save:function(){
		if(typeof localStorage !== 'undefined' && localStorage){
			localStorage.setItem(oc_current_user+'oc_playlist_items',JSON.stringify(PlayList.items));
			localStorage.setItem(oc_current_user+'oc_playlist_current',PlayList.current);
			var time=Math.round(PlayList.player.data('jPlayer').status.currentTime);
			localStorage.setItem(oc_current_user+'oc_playlist_time',time);
			var volume=PlayList.player.data('jPlayer').options.volume*100;
			localStorage.setItem(oc_current_user+'oc_playlist_volume',volume);
			if(PlayList.active){
				localStorage.setItem(oc_current_user+'oc_playlist_active','false');
			}
		}
	},
	load:function(){
		if(typeof localStorage !== 'undefined' && localStorage){
			PlayList.active=true;
			localStorage.setItem(oc_current_user+'oc_playlist_active','true');
			if(localStorage.hasOwnProperty(oc_current_user+'oc_playlist_items')){
				PlayList.items=JSON.parse(localStorage.getItem(oc_current_user+'oc_playlist_items'));
				if(PlayList.items.length>0){
					PlayList.current=parseInt(localStorage.getItem(oc_current_user+'oc_playlist_current'));
					var time=parseInt(localStorage.getItem(oc_current_user+'oc_playlist_time'));
					if(localStorage.hasOwnProperty(oc_current_user+'oc_playlist_volume')){
						var volume=localStorage.getItem(oc_current_user+'oc_playlist_volume');
						PlayList.volume=volume/100;
						$('.jp-volume-bar-value').css('width',volume+'%');
						if(PlayList.player.data('jPlayer')){
							PlayList.player.jPlayer("option",'volume',volume/100);
						}
					}
					if(JSON.parse(localStorage.getItem(oc_current_user+'oc_playlist_playing'))){
						PlayList.play(null,time);
					}else{
						PlayList.play(null,time,function(){
							PlayList.player.jPlayer("pause");
						});
					}
					PlayList.render();
				}
			}
		}
	}
}

$(document).ready(function(){
	$(window).bind('beforeunload', function (){
		PlayList.save();
	});
})
