/**
 * Copyright (c) 2012, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC.Log={
	reload:function(count){
		if(!count){
			count=OC.Log.loaded;
		}
		OC.Log.loaded=0;
		$('#log tbody').empty();
		OC.Log.getMore(count);
	},
	levels:['Debug','Info','Warning','Error','Fatal'],
	loaded:3,//are initially loaded
	getMore:function(count){
		if(!count){
			count=10;
		}
		$.get(OC.filePath('settings','ajax','getlog.php'),{offset:OC.Log.loaded,count:count},function(result){
			if(result.status=='success'){
				OC.Log.addEntries(result.data);
				$('html, body').animate({scrollTop: $(document).height()}, 800);
			}
		});
	},
	addEntries:function(entries){
		for(var i=0;i<entries.length;i++){
			var entry=entries[i];
			var row=$('<tr/>');
			var levelTd=$('<td/>');
			levelTd.text(OC.Log.levels[entry.level]);
			row.append(levelTd);
			
			var appTd=$('<td/>');
			appTd.text(entry.app);
			row.append(appTd);
			
			var messageTd=$('<td/>');
			messageTd.html(entry.message);
			row.append(messageTd);
			
			var timeTd=$('<td/>');
			timeTd.text(formatDate(entry.time*1000));
			row.append(timeTd);
			$('#log').append(row);
		}
		OC.Log.loaded += entries.length;
	}
}

$(document).ready(function(){
	$('#moreLog').click(function(){
		OC.Log.getMore();
	})
});
