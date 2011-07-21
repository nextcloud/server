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
		$('#file_menu').empty();
		parent.append($('#file_menu'));
		var actions=FileActions.get(FileActions.getCurrentMimeType(),FileActions.getCurrentType());
		for(name in actions){
			var html='<a href="#" alt="'+name+'">'+name+'</a>';
			var element=$(html);
			element.data('action',name);
			element.click(function(event){
				event.stopPropagation();
				event.preventDefault();
				var action=actions[$(this).data('action')];
				var currentFile=FileActions.getCurrentFile();
				FileActions.hide();
				action(currentFile);
			});
			$('#file_menu').append(element);
		}
		$('#file_menu').show();
		return false;
	},
	hide:function(){
		$('#file_menu').hide();
		$('#file_menu').empty();
		$('body').append($('#file_menu'));
	},
	getCurrentFile:function(){
		return $('#file_menu').parent().parent().attr('data-file');
	},
	getCurrentMimeType:function(){
		return $('#file_menu').parent().parent().attr('data-mime');
	},
	getCurrentType:function(){
		return $('#file_menu').parent().parent().attr('data-type');
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