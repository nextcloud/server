function musicTypeFromFile(file){
	var extention=file.substr(file.indexOf('.')+1);
	if(extention=='ogg'){
		return 'oga'
	}
	//TODO check for more specific cases
	return extention;
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
		OC.addScript('media','jquery.jplayer.min',function(){
			OC.addScript('media','player',function(){
				$('body').append($('<div id="playerPlaceholder"/>'))
				$('#playerPlaceholder').append($('<div/>')).load(OC.filePath('media','templates','player.php'),function(){
					loadPlayer.done=true;
					PlayList.init(type,ready);
				});
			});
		});
		OC.addStyle('media','player');
	}else{
		ready();
	}
}

$(document).ready(function() {
	loadPlayer.done=false

// 	FileActions.register('audio','Add to playlist','',addAudio);
// 	FileActions.register('application/ogg','Add to playlist','',addAudio);

	if(typeof FileActions!=='undefined'){
		FileActions.register('audio','Play','',playAudio);
		FileActions.register('application/ogg','','Play',playAudio);
		FileActions.setDefault('audio','Play');
		FileActions.setDefault('application/ogg','Play');
	}
	if(typeof PlayList==='undefined'){
		if(typeof localStorage !== 'undefined'){
			if(localStorage.hasOwnProperty(oc_current_user+'oc_playlist_items' && localStorage.getItem(oc_current_user+'oc_playlist_items')!='[]'){
				loadPlayer();
			}
		}
	}
});