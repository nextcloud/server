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