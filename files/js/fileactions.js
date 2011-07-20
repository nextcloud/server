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
		FileActions.defaults[mime]=name;
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
		var name=false;
		if(mime && FileActions.defaults[mime]){
			name=FileActions.defaults[mime];
		}else if(mime && FileActions.defaults[mimePart]){
			name=FileActions.defaults[mimePart];
		}else if(type && FileActions.defaults[type]){
			name=FileActions.defaults[type];
		}else{
			name=FileActions.defaults.all;
		}
		var actions=this.get(mime,type);
		return actions[name];
	},
	display:function(parent){
		$('#file_menu ul').empty();
		parent.append($('#file_menu'));
		var actions=FileActions.get(FileActions.getCurrentMimeType(),FileActions.getCurrentType());
		for(name in actions){
			var html='<li><a href="" alt="'+name+'">'+name+'</a></li>';
			var element=$(html);
			element.data('action',name);
			element.click(function(event){
				event.preventDefault();
				$('#file_menu').slideToggle(250);
				var action=actions[$(this).data('action')];
				$('#file_menu ul').empty();
				action(FileActions.getCurrentFile());
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
});

FileActions.register('all','Delete',function(filename){
	$.ajax({
		url: 'ajax/delete.php',
		data: "dir="+encodeURIComponent($('#dir').val())+"&file="+encodeURIComponent(filename),
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
});

FileActions.setDefault('dir','Open');