function musicTypeFromFile(file){
	var extension=file.substr(file.indexOf('.')+1).toLowerCase();
	if(extension=='ogg'){
		return 'oga';
	}
	//TODO check for more specific cases
	return extension;
}

function playAudio(filename){
	loadPlayer(musicTypeFromFile(filename),function(){
		PlayList.add($('#dir').val()+'/'+filename);
		PlayList.play(PlayList.items.length-1);
	});
}

function addAudio(filename){
	loadPlayer(musicTypeFromFile(filename),function(){
		PlayList.add($('#dir').val()+'/'+filename);
	});
}

function loadPlayer(type,ready){
	if(!loadPlayer.done){
		loadPlayer.done=true;
		OC.addStyle('media','player');
		OC.addScript('media','jquery.jplayer.min',function(){
			OC.addScript('media','player',function(){
				var navItem=$('#apps a[href="'+OC.linkTo('media','index.php')+'"]');
				navItem.height(navItem.height());
				navItem.load(OC.filePath('media','templates','player.php'),function(){
					PlayList.init(type,ready);
				});
			});
		});
	}else{
		ready();
	}
}

$(document).ready(function() {
	loadPlayer.done=false;

// 	FileActions.register('audio','Add to playlist','',addAudio);
// 	FileActions.register('application/ogg','Add to playlist','',addAudio);

	if(typeof FileActions!=='undefined'){
		FileActions.register('audio','Play','',playAudio);
		FileActions.register('application/ogg','','Play',playAudio);
		FileActions.setDefault('audio','Play');
		FileActions.setDefault('application/ogg','Play');
	}
	var oc_current_user=OC.currentUser;
	if(typeof PlayList==='undefined'){
		if(OC.localStorage.getItem('playlist_items') && OC.localStorage.getItem('playlist_items').length && OC.localStorage.getItem('playlist_active')!=true){
			loadPlayer();
		}
	}
});
