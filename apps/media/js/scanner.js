Scanner={
	songsFound:0,
	songsScanned:0,
	songsChecked:0,
	startTime:null,
	endTime:null,
	stopScanning:false,
	currentIndex:0,
	songs:[],
	findSongs:function(ready){
		$.getJSON(OC.linkTo('media','ajax/api.php')+'?action=find_music',function(songs){
			Scanner.songsFound=songs.length;
			Scanner.currentIndex=-1
			if(ready){
				
				ready(songs)
			}
		});
	},
	scanFile:function(path,ready){
		path=encodeURIComponent(path);
		$.getJSON(OC.linkTo('media','ajax/api.php')+'?action=get_path_info&path='+path,function(song){
			Scanner.songsChecked++;
			if(ready){
				ready(song);
			}
			if(song){//do this after the ready call so we dont hold up the next ajax call
				var artistId=song.song_artist;
				Scanner.songsScanned++;
				$('#scan span.songCount').text(Scanner.songsScanned);
				var progress=(Scanner.songsChecked/Scanner.songsFound)*100;
				$('#scanprogressbar').progressbar('value',progress)
				Collection.addSong(song);
			}
		});
	},
	scanCollection:function(ready){
		$('#scanprogressbar').progressbar({
			value:0,
		});
		$('#scanprogressbar').show();
		Scanner.songsChecked=0;
		Scanner.currentIndex=0;
		Scanner.songsScanned=0;
		Scanner.startTime=new Date().getTime()/1000;
		Scanner.findSongs(function(songs){
			Scanner.songs=songs;
			Scanner.start(function(){
				$('#scan input.start').show();
				$('#scan input.stop').hide();
				$('#scanprogressbar').hide();
				Collection.display();
				if(ready){
					ready();
				}
			});
		});
	},
	stop:function(){
		Scanner.stopScanning=true;
	},
	start:function(ready){
		Scanner.stopScanning=false;
		$('#scancount').show();
		var scanSong=function(){
			if(!Scanner.stopScanning && Scanner.currentIndex<=Scanner.songs.length){
				Scanner.scanFile(Scanner.songs[Scanner.currentIndex],scanSong)
			}else if(!Scanner.stopScanning){
				Scanner.endTime=new Date().getTime()/1000;
				if(ready){
					ready();
					ready=null;//only call ready once
				}
			}
			Scanner.currentIndex++;
		}
		scanSong();
		scanSong();
	},
	toggle:function(){
		if(Scanner.stopScanning){
			Scanner.start();
			$('#scan input.stop').val(t('media','Pause'));
		}else{
			Scanner.stop();
			$('#scan input.stop').val(t('media','Resume'));
		}
	}

}
