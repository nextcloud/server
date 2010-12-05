OC_AudioPlayer = new Object();

OC_AudioPlayer.playAudio = function(dir, file, type) {
	var path = WEBROOT + '/files/api?action=get&dir='+encodeURIComponent(dir)+'&file='+encodeURIComponent(file);
	
	OC_AudioPlayer.audioFrame = document.createElement('div');
	OC_AudioPlayer.audioFrame.setAttribute('id', 'audioframe');
	OC_AudioPlayer.audioFrame.setAttribute('class', 'center');
	var div = document.createElement('div');
	var inner = document.createElement('div');
	var audio = document.createElement('audio');
	var source = document.createElement('source');
	
// 	if (!(!!(audio.canPlayType) && (audio.canPlayType(type) != "no") && (audio.canPlayType(type) != ""))) {
// 		// use a flash player fallback
// 		// or implement some nice on-the-fly recoding here
// 		alert("Native playing of '"+type+"' format is not supported by your browser.");
// 		return;
// 	}
	audio.setAttribute('controls', 'controls');
	audio.setAttribute('preload', 'auto');
	audio.setAttribute('autoplay', 'autoplay');
	audio.setAttribute('autobuffer', 'autobuffer');
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

// only register "play" option for file formats the browser claims to support
OC_AudioPlayer.formats = {
	'audio/mpeg':"mp3",
	'audio/ogg':"ogg",
	'application/ogg':"ogg",
	'audio/wav':"wav",
	'audio/wave':"wav",
	'audio/x-wav':"wav",
	'audio/basic':"au",
	'audio/x-aiff':"aif"
};
var audio = document.createElement('audio');
for(format in OC_AudioPlayer.formats) {
	if (!!(audio.canPlayType) && (audio.canPlayType(format) != "no") && (audio.canPlayType(format) != "")) {
		if(!OC_FILES.fileActions[format]) {
			OC_FILES.fileActions[format] = new Object();
		}
		OC_FILES.fileActions[format].play = function() {
			OC_AudioPlayer.playAudio(this.dir, this.file, this.mime);
		}
		OC_FILES.fileActions[format]['default'] = OC_FILES.fileActions[format].play;
	}
}
