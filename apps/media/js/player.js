var PlayList={
	urlBase:OC.linkTo('media','ajax/api.php')+'?action=play&path=',
	current:-1,
	items:[],
	player:null,
	volume:0.8,
	active:false,
	next:function(){
		var items=PlayList.items;
		var next=PlayList.current+1;
		if(next>=items.length){
			next=0;
		}
		PlayList.play(next);
		PlayList.render();
	},
	previous:function(){
		var items=PlayList.items;
		var next=PlayList.current-1;
		if(next<0){
			next=items.length-1;
		}
		PlayList.play(next);
		PlayList.render();
	},
	play:function(index,time,ready){
		var items=PlayList.items;
		if(index==null){
			index=PlayList.current;
		}
		PlayList.save();
		if(index>-1 && index<items.length){
			PlayList.current=index;
			if(PlayList.player){
				if(PlayList.player.data('jPlayer').options.supplied!=items[index].type){//the the audio type changes we need to reinitialize jplayer
					PlayList.player.jPlayer("play",time);
					OC.localStorage.setItem('playlist_time',time);
					PlayList.player.jPlayer("destroy");
// 					PlayList.save(); // so that the init don't lose the playlist
					PlayList.init(items[index].type,null); // init calls load that calls play
				}else{
					PlayList.player.jPlayer("setMedia", items[PlayList.current]);
					$(".jp-current-song").text(items[PlayList.current].name);
					items[index].playcount++;
					PlayList.player.jPlayer("play",time);
					if(index>0){
						var previous=index-1;
					}else{
						var previous=items.length-1;
					}
					if(index+1<items.length){
						var next=index+1;
					}else{
						var next=0;
					}
					$('.jp-next').attr('title',items[next].name);
					$('.jp-previous').attr('title',items[previous].name);
					if (typeof Collection !== 'undefined') {
						Collection.registerPlay();
					}
					PlayList.render();
					if(ready){
						ready();
					}
				}
			}else{
				OC.localStorage.setItem('playlist_time',time);
				OC.localStorage.setItem('playlist_playing',true);
				PlayList.init(items[index].type,null); // init calls load that calls play
			}
		}
		$(".song").removeClass("collection_playing");
		$(".jp-playlist-" + index).addClass("collection_playing");
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
			PlayList.player=$('#jp-player');
		}
		$(PlayList.player).jPlayer({
			ended:PlayList.next,
			pause:function(){
				OC.localStorage.setItem('playlist_playing',false);
				document.title = "ownCloud";
			},
			play:function(event){
				OC.localStorage.setItem('playlist_playing',true);
				document.title = "\u25b8 " + event.jPlayer.status.media.name + " - " + event.jPlayer.status.media.artist + " - ownCloud";
			},
			supplied:type,
			ready:function(){
				PlayList.load();
				if(ready){
					ready();
				}
			},
			volume:PlayList.volume,
			cssSelectorAncestor:'.player-controls',
			swfPath:OC.linkTo('media','js'),
		});
	},
	add:function(song,dontReset){
		if(!dontReset){
			PlayList.items=[];//clear the playlist
		}
		if(!song){
			return;
		}
		if(song.substr){//we are passed a string, asume it's a url to a song
			PlayList.addFile(song,true);
		}
		if(song.albums){//a artist object was passed, add all albums inside it
			$.each(song.albums,function(index,album){
				PlayList.add(album,true);
			});
		} else if(song.songs){//a album object was passed, add all songs inside it
			$.each(song.songs,function(index,song){
				PlayList.add(song,true);
			});
		}
		if(song.path){
			var type=musicTypeFromFile(song.path);
			var item={name:song.name,type:type,artist:song.artist,album:song.album,length:song.length,playcount:song.playCount};
			item[type]=PlayList.urlBase+encodeURIComponent(song.path);
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
		OC.localStorage.setItem('playlist_items',PlayList.items);
		OC.localStorage.setItem('playlist_current',PlayList.current);
		if(PlayList.player) {
			if(PlayList.player.data('jPlayer')) {
				var time=Math.round(PlayList.player.data('jPlayer').status.currentTime);
				OC.localStorage.setItem('playlist_time',time);
				var volume=PlayList.player.data('jPlayer').options.volume*100;
				OC.localStorage.setItem('playlist_volume',volume);
			}
		}
		OC.localStorage.setItem('playlist_active',true);
	},
	load:function(){
		PlayList.active=true;
		OC.localStorage.setItem('playlist_active',true);
		if(OC.localStorage.hasItem('playlist_items')){
			PlayList.items=OC.localStorage.getItem('playlist_items');
			if(PlayList.items && PlayList.items.length>0){
				PlayList.current=OC.localStorage.getItem('playlist_current');
				var time=OC.localStorage.getItem('playlist_time');
				if(OC.localStorage.hasItem('playlist_volume')){
					var volume=OC.localStorage.getItem('playlist_volume');
					PlayList.volume=volume/100;
					$('.jp-volume-bar-value').css('width',volume+'%');
					if(PlayList.player.data('jPlayer')){
						PlayList.player.jPlayer("option",'volume',volume/100);
					}
				}
				if(OC.localStorage.getItem('playlist_playing')){
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

$(document).ready(function(){
	$(window).bind('beforeunload', function (){
		PlayList.save();
		if(PlayList.active){
			OC.localStorage.setItem('playlist_active',false);
		}
	});

	$('jp-previous').tipsy({gravity:'n', fade:true, live:true});
	$('jp-next').tipsy({gravity:'n', fade:true, live:true});
})
