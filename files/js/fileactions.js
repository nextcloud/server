FileActions={
	actions:{},
	defaults:{},
	icons:{},
	currentFile:null,
	register:function(mime,name,icon,action){
		if(!FileActions.actions[mime]){
			FileActions.actions[mime]={};
		}
		FileActions.actions[mime][name]=action;
		FileActions.icons[name]=icon;
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
		FileActions.currentFile=parent;
		$('.action').remove();
		var actions=FileActions.get(FileActions.getCurrentMimeType(),FileActions.getCurrentType());
		var file=FileActions.getCurrentFile();
		if($('tr[data-file="'+file+'"]').data('renaming')){
			return;
		}
		var defaultAction=FileActions.getDefault(FileActions.getCurrentMimeType(),FileActions.getCurrentType());
		for(name in actions){
			if((name=='Download' || actions[name]!=defaultAction) && name!='Delete'){
				var img=FileActions.icons[name];
				if(img.call){
					img=img(file);
				}
				var html='<a href="#" title="'+name+'" class="action" />';
				var element=$(html);
				if(img){
					element.append($('<img src="'+img+'"/>'));
				}
				element.data('action',name);
				element.click(function(event){
					event.stopPropagation();
					event.preventDefault();
					var action=actions[$(this).data('action')];
					var currentFile=FileActions.getCurrentFile();
					FileActions.hide();
					action(currentFile);
				});
				parent.children('a.name').append(element);
			}
		}
		if(actions['Delete']){
			var img=FileActions.icons['Delete'];
			if(img.call){
				img=img(file);
			}
			var html='<a href="#" title="Delete" class="action" />';
			var element=$(html);
			if(img){
				element.append($('<img src="'+img+'"/>'));
			}
			element.data('action','Delete');
			element.click(function(event){
				event.stopPropagation();
				event.preventDefault();
				var action=actions[$(this).data('action')];
				var currentFile=FileActions.getCurrentFile();
				FileActions.hide();
				action(currentFile);
			});
			parent.parent().children().last().append(element);
		}
		$('.action').hide();
		$('.action').fadeIn(200);
		return false;
	},
	hide:function(){
		$('.action').fadeOut(200,function(){
			$(this).remove();
		});
	},
	getCurrentFile:function(){
		return FileActions.currentFile.parent().attr('data-file');
	},
	getCurrentMimeType:function(){
		return FileActions.currentFile.parent().attr('data-mime');
	},
	getCurrentType:function(){
		return FileActions.currentFile.parent().attr('data-type');
	}
}

FileActions.register('all','Download',function(){return OC.imagePath('core','actions/download')},function(filename){
	window.location='ajax/download.php?files='+filename+'&dir='+$('#dir').val();
});

FileActions.register('all','Delete',function(){return OC.imagePath('core','actions/delete')},function(filename){
	FileList.do_delete(filename);
});

FileActions.register('all','Rename',function(){return OC.imagePath('core','actions/rename')},function(filename){
	FileList.rename(filename);
});

//FileActions.setDefault('all','Download');

FileActions.register('dir','Open','',function(filename){
	window.location='index.php?dir='+$('#dir').val()+'/'+filename;
});

FileActions.setDefault('dir','Open');
