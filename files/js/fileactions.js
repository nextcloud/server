FileActions={
	actions:{},
	defaults:{},
	register:function(mime,name,action){
		if(!FileActions.actions[mime]){
			FileActions.actions[mime]={};
		}
		FileActions.actions[mime][name]=action;
	},
	setDefault:function(mime,name){
		FileActions.defaults[mime]=FileActions.actions[mime][name];
	},
	get:function(mime,type){
		var actions={};
		if(FileActions.actions.all){
			actions=$.extend( actions, FileActions.actions.all )
		}
		if(mime){
			if(FileActions.actions[mime]){
				actions=$.extend( actions, FileActions.actions[mime] )
			}
			var mimePart=mime.substr(0,mime.indexOf('/'));
			if(FileActions.actions[mimePart]){
				actions=$.extend( actions, FileActions.actions[mimePart] )
			}
		}
		if(type){//type is 'dir' or 'file'
			if(FileActions.actions[type]){
				actions=$.extend( actions, FileActions.actions[type] )
			}
		}
		return actions;
	},
	getDefault:function(mime,type){
		if(mime){
			var mimePart=mime.substr(0,mime.indexOf('/'));
		}
		if(mime && FileActions.defaults[mime]){
			return FileActions.defaults[mime];
		}else if(mime && FileActions.defaults[mimePart]){
			return FileActions.defaults[mimePart];
		}else if(type && FileActions.defaults[type]){
			return FileActions.defaults[type];
		}else{
			return FileActions.defaults.all;
		}
	},
	display:function(parent){
		$('#file_menu>ul').empty();
		parent.append($('#file_menu'));
		var actions=FileActions.get(FileActions.getCurrentMimeType(),FileActions.getCurrentType());
		for(name in actions){
			var html='<li><a href="" alt="'+name+'">'+name+'</a></li>';
			var element=$(html);
			element.data('action',name);
			element.click(function(event){
				event.preventDefault();
				actions[$(this).data('action')](FileActions.getCurrentFile());
			});
			$('#file_menu>ul').append(element);
		}
		$('#file_menu').slideToggle(250);
		return false;
	},
	getCurrentFile:function(){
		return $('#file_menu').parents('tr:first').attr('data-file');
	},
	getCurrentMimeType:function(){
		return $('#file_menu').parents('tr:first').attr('data-mime');
	},
	getCurrentType:function(){
		return $('#file_menu').parents('tr:first').attr('data-type');
	}
}

FileActions.register('all','Download',function(filename){
	window.location='ajax/download.php?files='+filename+'&dir='+$('#dir').val();
	$('#file_menu').slideToggle(250);
});

FileActions.register('all','Delete',function(filename){
	$.ajax({
		url: 'ajax/delete.php',
		data: "dir="+$('#dir').val()+"&file="+filename,
		complete: function(data){
			boolOperationFinished(data, function(){
				FileList.remove(filename);
			});
		}
	});
});

FileActions.setDefault('all','Download');

FileActions.register('dir','Open',function(filename){
	window.location='index.php?dir='+$('#dir').val()+'/'+filename;
	$('#file_menu').slideToggle(250);
});

FileActions.setDefault('dir','Open');