$(document).ready(function(){
	//load the collection
	$('#plugins a[href="#collection"]').click(function(){
		$('#plugins li.subentry a.active').removeClass('active');
		$(this).addClass('active');
		PlayList.hide();
		Collection.display();
	});
	$('#plugins a[href="#playlist"]').click(function(){
		$('#plugins li.subentry a.active').removeClass('active');
		$(this).addClass('active');
		PlayList.render();
		Collection.hide();
	});
	var tab=window.location.href.slice(window.location.href.indexOf('#') + 1);
	if(tab=='collection'){
		$('#plugins a[href="#collection"]').trigger('click');
	}
	OC.search.customResults.Music=function(row,item){
		var parts=item.link.substr(item.link.indexOf('#')+1).split('&');
		var data={};
		for(var i=0;i<parts.length;i++){
			var itemParts=parts[i].split('=');
			data[itemParts[0]]=decodeURIComponent(itemParts[1]).replace(/\+/g,' ');
		}
		var media=Collection.find(data.artist,data.album,data.song);
		var a=row.find('a');
		a.attr('href','#');
		a.click(function(){
			var oldSize=PlayList.items.length;
			PlayList.add(media);
			PlayList.play(oldSize);
			PlayList.render();
		});
		var button=$('<input type="button" title="Add to playlist" class="add"></input>');
		button.css('background-image','url('+OC.imagePath('core','actions/play-add')+')')
		button.click(function(event){
			event.stopPropagation();
			PlayList.add(media);
			PlayList.render();
		});
		row.find('div.name').append(button);
	}
});



function getUrlVars(){
	var vars = [], hash;
	var hashes = window.location.href.slice(window.location.href.indexOf('#') + 1).split('&');
	for(var i = 0; i < hashes.length; i++)
	{
		hash = hashes[i].split('=');
		vars.push(hash[0]);
		vars[hash[0]] = decodeURIComponent(hash[1]).replace(/\+/g,' ');
	}
	return vars;
}

function musicTypeFromFile(file){
	var extention=file.split('.').pop();
	if(extention=='ogg'){
		return 'oga'
	}
	//TODO check for more specific cases
	return extention;
}