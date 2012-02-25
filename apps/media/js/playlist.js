PlayList.render=function(){
	$('#playlist').show();

	/*
	 * We should not empty() PlayList.parent() but thorougly manage its
	 * elements instead because some code might be attached to those.
	 * JQuery tipsies are one of them. The following line make sure they
	 * are all removed before we delete the associated <li/>.
	 */
	$(".tipsy").remove();

	PlayList.parent.empty();
	for(var i=0;i<PlayList.items.length;i++){
		var item=PlayList.items[i];
		var li=$('<li/>');
		li.attr('class', 'jp-playlist-' + i);
		li.attr('title', item.artist + ' - ' + item.name + '<br/>(' + item.album + ')');
		var div = $('<div class="label">' + item.name + '</div>');
		li.append(div);
		$('.jp-playlist-' + i).tipsy({gravity:'w', fade:true, live:true, html:true});
		var img=$('<img class="remove svg action" src="'+OC.imagePath('core','actions/delete')+'"/>');
		img.click(function(event){
			event.stopPropagation();
			PlayList.remove($(this).parent().data('index'));
		});
		li.click(function(event){
			PlayList.play($(this).data('index'));
		});
		li.append(img);
		li.data('index',i);
		li.addClass('song');
		PlayList.parent.append(li);
	}
        $(".jp-playlist-" + PlayList.current).addClass("collection_playing");
};
PlayList.getSelected=function(){
	return $('tbody td.name input:checkbox:checked').parent().parent();
};
PlayList.hide=function(){
	$('#playlist').hide();
};

$(document).ready(function(){
	PlayList.parent=$('#leftcontent');
	PlayList.init();
	$('#selectAll').click(function(){
		if($(this).attr('checked')){
			// Check all
			$('#leftcontent li.song input:checkbox').attr('checked', true);
			$('#leftcontent li.song input:checkbox').parent().addClass('selected');
		}else{
			// Uncheck all
			$('#leftcontent li.song input:checkbox').attr('checked', false);
			$('#leftcontent li.song input:checkbox').parent().removeClass('selected');
		}
	});
});
