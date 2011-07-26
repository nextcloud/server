var PlayList={
	urlBase:OC.linkTo('media','ajax/api.php')+'?action=play&path=',
	current:-1,
	items:[],
	player:null,
	parent:null,
	next:function(){
		var next=PlayList.current+1;
		if(next>=PlayList.items.length){
			next=0;
		}
		PlayList.play(next);
	},
	previous:function(){
		var next=PlayList.current-1;
		if(next<0){
			next=PlayList.items.length-1;
		}
		PlayList.play(next);
	},
	play:function(index){
		if(index==null){
			index=PlayList.current;
		}
		if(index>-1 && index<PlayList.items.length){
			PlayList.current=index;
			if(PlayList.player){
				if(PlayList.player.data('jPlayer').options.supplied!=PlayList.items[index].type){//the the audio type changes we need to reinitialize jplayer
					PlayList.player.jPlayer("destroy");
					PlayList.init(PlayList.items[index].type,PlayList.play);
				}else{
					PlayList.player.jPlayer("setMedia", PlayList.items[PlayList.current]);
					PlayList.player.jPlayer("play");
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
			supplied:type,
			ready:function(){
				if(ready){
					ready();
				}
			},
			cssSelectorAncestor:'#jp-interface',
		});
	},
	add:function(song){
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
			var item={name:song.song_name,type:type,artist:song.artist_name,album:song.album_name};
			item[type]=PlayList.urlBase+encodeURIComponent(song.song_path);
			PlayList.items.push(item);
		}
	},
	addFile:function(path){
		var type=musicTypeFromFile(path);
		var item={name:'unknown',artist:'unknown',album:'unknwon',type:type};//todo get song data
		item[type]=PlayList.urlBase+encodeURIComponent(path);
		PlayList.items.push(item);
	},
	render:function(parent){//parent should be an ul element
		if(parent){
			PlayList.parent=parent;
		}else{
			parent=PlayList.parent;
		}
		if(parent){
			parent.empty();
			for(var i=0;i<PlayList.items.length;i++){
				var song=PlayList.items[i];
				var item=$('<li>'+song.artist+' - '+song.album+' - '+song.name+'</li>');
				item.data('artist',song.artist);
				item.data('album',song.album);
				item.data('name',song.name);
				item.data('index',i);
				item.click(function(){
					PlayList.play($(this).data('index'));
					PlayList.render();
				});
				if(i==PlayList.current){
					item.addClass('current');
				}
				parent.append(item);
			}
		}
	}
}
