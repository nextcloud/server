Scanner={
	songsFound:0,
	eventSource:null,
	songsScanned:0,
	findSongs:function(ready){
		$.getJSON(OC.linkTo('media','ajax/api.php')+'?action=find_music',function(songs){
			Scanner.songsFound=songs.length;
			if(ready){
				ready(songs);
			}
		});
	},
	scanCollection:function(ready){
		$('#scanprogressbar').progressbar({
			value:0,
		});
		$('#scanprogressbar').show();
		Scanner.songsScanned=0;
		Scanner.eventSource=new OC.EventSource(OC.linkTo('media','ajax/api.php'),{action:'scan'});
		Scanner.eventSource.listen('count',function(total){
			Scanner.songsFound=total;
		});
		Scanner.eventSource.listen('scanned',function(data){
			Scanner.songsScanned=data.count;
			$('#scan span.songCount').text(Scanner.songsScanned);
			var progress=(Scanner.songsScanned/Scanner.songsFound)*100;
			$('#scanprogressbar').progressbar('value',progress);
		});
		Scanner.eventSource.listen('done',function(count){
			$('#scan input.start').show();
			$('#scan input.stop').hide();
			$('#scanprogressbar').hide();
			Collection.load(Collection.display);
			if(ready){
				ready();
			}
		});
		$('#scancount').show();
	},
	stop:function(){
		Scanner.eventSource.close();
	},

};
