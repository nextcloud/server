OC_AudioPlayer = new Object();

OC_AudioPlayer.playAudio = function(dir, file, type) {
	var path = WEBROOT + '/files/open_file.php?dir='+encodeURIComponent(dir)+'&file='+encodeURIComponent(file);
	
	OC_AudioPlayer.audioFrame = document.createElement('div');
	OC_AudioPlayer.audioFrame.setAttribute('id', 'audioframe');
	OC_AudioPlayer.audioFrame.setAttribute('class', 'center');
	var div = document.createElement('div');
	var inner = document.createElement('div');
	var audio = document.createElement('audio');
	var source = document.createElement('source');
	
	if (!(!!(audio.canPlayType) && (audio.canPlayType(type) != "no") && (audio.canPlayType(type) != ""))) {
		// use a flash player fallback
		// or implement some nice on-the-fly recoding here
		alert("Native playing of '"+type+"' format is not supported by your browser.");
		return;
	}
	audio.setAttribute('controls', 'true');
	audio.setAttribute('preload', 'auto');
	audio.setAttribute('autoplay', 'true');
	audio.setAttribute('autobuffer', 'true');
	source.setAttribute('src', path);
	source.setAttribute('type', type);
	
	audio.appendChild(source);
	inner.appendChild(audio);
	div.appendChild(inner);
	OC_AudioPlayer.audioFrame.appendChild(div);
	
	OC_AudioPlayer.audioFrame.addEvent('onclick', OC_AudioPlayer.hidePlayer);
	inner.addEvent('onclick', function(e){e.stopPropagation();}); // don't close if clicked on player
	
	body = document.getElementsByTagName('body').item(0);
	body.appendChild(OC_AudioPlayer.audioFrame);
}

OC_AudioPlayer.hidePlayer = function(){
	var div = document.getElementById('audioframe');
	div.parentNode.removeChild(div);
} 


if(!OC_FILES.fileActions.audio){
	OC_FILES.fileActions.audio = new Object();
}
if(!OC_FILES.fileActions.applicationogg){
	OC_FILES.fileActions.applicationogg = new Object();
}

OC_FILES.fileActions.audio.play = function() {
	OC_AudioPlayer.playAudio(this.dir, this.file, this.mime);
}
OC_FILES.fileActions.applicationogg.play = function() {
	OC_AudioPlayer.playAudio(this.dir, this.file, this.mime);
}

OC_FILES.fileActions.audio['default'] = OC_FILES.fileActions.audio.play;
OC_FILES.fileActions.applicationogg['default'] = OC_FILES.fileActions.applicationogg.play;
