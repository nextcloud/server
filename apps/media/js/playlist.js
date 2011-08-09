PlayList.render=function(){
	$('#playlist').show();
	PlayList.parent.empty();
	for(var i=0;i<PlayList.items.length;i++){
		var tr=PlayList.template.clone();
		var item=PlayList.items[i];
		if(i==PlayList.current){
			tr.addClass('current');
		}
		tr.removeClass('template');
		tr.data('name',item.name);
		tr.data('artist',item.artist);
		tr.children('td.name').children('span').text(item.name);
		tr.children('td.artist').text(item.artist);
		tr.children('td.album').text(item.album);
		tr.data('index',i);
		tr.click(function(){
			PlayList.play($(this).data('index'));
			PlayList.render();
		});
		tr.hover(function(){
			var button=$('<img class="remove" title="Remove"/>');
			button.attr('src',OC.imagePath('core','actions/delete'));
			$(this).children().last().append(button);
			button.click(function(event){
				event.stopPropagation();
				event.preventDefault();
				var index=$(this).parent().parent().data('index');
				PlayList.remove(index);
			});
		},function(){
			$(this).children().last().children('img.remove').remove();
		});
		tr.children('td.name').children('input').click(function(event){
			event.stopPropagation();
			if($(this).attr('checked')){
				$(this).parent().parent().addClass('selected');
				if($('tbody td.name input:checkbox').length==$('tbody td.name input:checkbox:checked').length){
					$('#selectAll').attr('checked',true);
				}
			}else{
				$(this).parent().parent().removeClass('selected');
				$('#selectAll').attr('checked',false);
			}
			procesSelection();
		});
		PlayList.parent.append(tr);
	}
}
PlayList.getSelected=function(){
	return $('tbody td.name input:checkbox:checked').parent().parent();
}
PlayList.hide=function(){
	$('#playlist').hide();
}

$(document).ready(function(){
	PlayList.parent=$('#playlist tbody');
	PlayList.template=$('#playlist tr.template');
	$('#selectAll').click(function(){
		if($(this).attr('checked')){
			// Check all
			$('tbody td.name input:checkbox').attr('checked', true);
			$('tbody td.name input:checkbox').parent().parent().addClass('selected');
		}else{
			// Uncheck all
			$('tbody td.name input:checkbox').attr('checked', false);
			$('tbody td.name input:checkbox').parent().parent().removeClass('selected');
		}
		procesSelection();
	});
});

function procesSelection(){
	var selected=PlayList.getSelected();
	if(selected.length==0){
		$('th.name span').text('Name');
		$('th.artist').text('Artist');
		$('th.album').text('Album');
		$('th.time').text('Time');
		$('th.plays').empty();
		$('th.plays').text('Plays');
	}else{
		var name=selected.length+' selected';
		var artist=$(selected[0]).data('artist');
		var album=$(selected[0]).data('album');
		var time=$(selected[0]).data('time');
		var plays=$(selected[0]).data('plays');
		for(var i=1;i<selected.length;i++){
			var item=$(selected[i]);
			if(artist!='mixed' && item.data('artist')!==artist){
				artist='mixed'
			}
			if(album!='mixed' && item.data('album')!==album){
				album='mixed'
			}
			if(time!='mixed' && item.data('time')!==time){
				time='mixed'
			}
			if(plays!='mixed' && item.data('plays')!==plays){
				plays='mixed'
			}
		}
		$('th.name span').text(name);
		$('th.artist').text(artist);
		$('th.album').text(album);
		if(time!='mixed'){
			var secconds=(time%60);
			if(secconds<10){
				secconds='0'+secconds;
			}
			var time=Math.floor(time/60)+':'+secconds;
		}
		$('th.time').text(time);
		$('th.plays').text(plays);
		var button=$('<img class="remove" title="Remove"/>');
		button.attr('src',OC.imagePath('core','actions/delete'));
		$('th.plays').append(button);
		button.click(function(event){
			event.stopPropagation();
			event.preventDefault();
			PlayList.getSelected().each(function(index,element){
				var index=$(element).data('index');
				PlayList.items[index]=null;
			});
			PlayList.items=PlayList.items.filter(function(item){return item!==null});
			PlayList.render();
			PlayList.save();
			procesSelection();
		});
	}
}