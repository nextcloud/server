/**
* ownCloud - ajax frontend
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmail.com
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

OC_FILES=new Object();

OC_FILES.cache=new Object();

OC_FILES.cache.files=Array();
OC_FILES.cache.incomplete=Array();
OC_FILES.cache.actions=new Object();

OC_FILES.cache.actions.move=Array();
OC_FILES.cache.actions.rename=Array();
OC_FILES.cache.actions['new']=Array();
OC_FILES.cache.actions['delete']=Array();
OC_FILES.cache.actions.upload=Array();

OC_FILES.cache.refresh=function(){
	OC_FILES.getdirectorycontent(OC_FILES.dir,false,true);
}

OC_FILES.xmlloader=new OCXMLLoader();

OC_FILES.getdirectorycontent_parse=function(req){
	var files=new Array();
	var json=eval('('+req.responseText+')');
	OC_FILES.cache.files=Array();
	if(json){
		for(var name in json){
			if(name!='__max_upload'){
				var file=new Array();
				var attributes=Array('size','name','type','directory','date','mime');
				for(var i in attributes){
					var attributeName=attributes[i];
					file[attributeName]=json[name][attributeName];
				}
				files[file.name]=file;
			}
		}
		OC_FILES.cache.files=files;
		if(OC_FILES.cache.incomplete[OC_FILES.dir]){
			files=arrayMerge(files,OC_FILES.cache.incomplete[OC_FILES.dir]);
		}
		files['max_upload']=json['__max_upload'];
		if(OC_FILES.getdirectorycontent_callback){
			OC_FILES.getdirectorycontent_callback(files);
		}
	}
}

OC_FILES.getdirectorycontent=function(dir,callback,refresh){
	if(refresh || OC_FILES.dir!=dir){
		OC_FILES.dir=dir;
		if(callback){
			OC_FILES.getdirectorycontent_callback=callback;
		}
		OC_FILES.xmlloader.setCallBack(OC_FILES.getdirectorycontent_parse);
// 		OC_FILES.xmlloader.load('files/get_files.php?dir='+encodeURIComponent(dir));
		OC_FILES.xmlloader.load('files/api.php?action=getfiles&dir='+encodeURIComponent(dir));
	}else{
		var files=OC_FILES.cache.files
		if(OC_FILES.cache.incomplete[OC_FILES.dir]){
			files=arrayMerge(files,OC_FILES.cache.incomplete[OC_FILES.dir]);
		}
		callback(files);
	}
}

OC_FILES.dir='';

OC_FILES.get=function(dir,file){
	window.location='files/api.php?action=get&dir='+encodeURIComponent(dir)+'&file='+encodeURIComponent(file);
}

OC_FILES.upload=function(iframeId){
	var dir=OC_FILES.dir;
	var file=new Object;
	var fileSelector=document.getElementById('fileSelector');
	var max_upload=document.getElementById('max_upload').value;
	var name=false;
	if(fileSelector.files && fileSelector.files[0].fileName){
		name=fileSelector.files[0].fileName;
	}
	if(fileSelector.files && fileSelector.files[0].fileSize){
		var size=fileSelector.files[0].fileSize;
		if(size>max_upload){
			new OCNotification('File too large',10000)
			return false;
		}
	}
	var mime='';
	if(fileSelector.files && fileSelector.files[0].type){
		var mime=fileSelector.files[0].type;
	}
	file.dir=OC_FILES.dir;
	file.name=name;
	file.type='file';
	file.size=size;
	file.iframeId=iframeId;
	if(!OC_FILES.cache.incomplete[dir]){
		OC_FILES.cache.incomplete[dir]=Array();
	}
	OC_FILES.cache.incomplete[dir][name]=Array();
	OC_FILES.cache.incomplete[dir][name]['name']=name;
	OC_FILES.cache.incomplete[dir][name]['type']='incomplete';
	OC_FILES.cache.incomplete[dir][name]['size']=size;
	OC_FILES.cache.incomplete[dir][name]['mime']=mime;
	OC_FILES.uploadIFrames[iframeId].file=file;
	OC_FILES.uploadIFrames[iframeId].addEvent('onload',new callBack(OC_FILES.upload_callback,OC_FILES.uploadIFrames[iframeId]));
	OC_FILES.browser.files.add(name,'incomplete',size,null,mime);
	OC_FILES.uploadForm.submit();
	if(OC_FILES.uploadForm.parentElement){
		OC_FILES.uploadForm.className='hidden';
		OC_FILES.uploadForm.parentNode.removeChild(OC_FILES.uploadForm);
		var body=document.getElementsByTagName('body').item(0);
		body.appendChild(OC_FILES.uploadForm);
		OC_FILES.uploadIFrames[iframeId].uploadForm=OC_FILES.uploadForm;
		OC_FILES.browser.showuploader(OC_FILES.dir,OC_FILES.uploadIFrames[iframeId].uploadParent,OC_FILES.maxUpload)
	}
}

OC_FILES.upload_callback=function(iframeId){
	var file=this.file;
	if(OC_FILES.cache.incomplete[file.dir][file.name]){
		OC_FILES.browser.files.remove(file.name);
		OC_FILES.cache.files[file.name]=OC_FILES.cache.incomplete[file.dir][file.name]
		delete OC_FILES.cache.incomplete[file.dir][file.name];
		OC_FILES.cache.files[file.name]['type']=file.type;
		this.uploadForm.parentNode.removeChild(this.uploadForm);
		this.parentNode.removeChild(this);
		OC_FILES.uploadIFrames[file.iframeId]=null;
		if(file.name){
			OC_FILES.browser.show(file.dir);
		}else{
			OC_FILES.browser.show(file.dir,true);//if the data from the file isn't correct, force a reload of the cache
		}
	}else{
		OC_FILES.browser.show(OC_FILES.dir);
	}
}

OC_FILES.rename=function(dir,file,event){
	if(event && event.preventDefault){
		event.preventDefault();
	}
	var item=document.getElementById(file+'_newname');
	var newname=item.value;
	if(newname==''){
		return false;
	}else if(file==newname){
		OC_FILES.browser.show(OC_FILES.dir);
		return false;
	}
	arg=new Object;
	arg.oldname=file;
	arg.newname=newname;
	arg.dir=dir;
	arg.type=OC_FILES.cache.files[file]['type'];
	OC_API.run('rename',{dir:dir,file:file,newname:newname},OC_FILES.rename_callback,arg)
	if(!OC_FILES.cache.incomplete[dir]){
		OC_FILES.cache.incomplete[dir]=Array();
	}
	OC_FILES.cache.files[file]['type']='incomplete';
	OC_FILES.cache.incomplete[dir][newname]=OC_FILES.cache.files[file];
	OC_FILES.cache.incomplete[dir][newname]['name']=newname;
	OC_FILES.browser.files.remove(file);
	OC_FILES.browser.files.add(newname,'incomplete');
	return false;
}


OC_FILES.rename_callback=function(req,file){
	delete OC_FILES.cache.files[file.oldname]
	OC_FILES.cache.files[file.newname]=OC_FILES.cache.incomplete[file.dir][file.newname];
	delete OC_FILES.cache.incomplete[file.dir][file.newname];
	OC_FILES.browser.files.remove(file.newname);
	OC_FILES.cache.files[file.newname]['type']=file.type;
	OC_FILES.browser.show(OC_FILES.dir);
}

OC_FILES.remove=function(dir,file){
	remove=confirm('Delete file \''+file+'\'?');
	if(remove){
		OC_API.run('delete',{dir:dir,file:file},OC_FILES.remove_callback,file)
		OC_FILES.browser.files.remove(file);
		delete OC_FILES.cache.files[file];
	}
}

OC_FILES.remove_callback=function(req,name){
// 	OC_FILES.browser.files.remove(name);
//    OC_FILES.browser.show(OC_FILES.dir);
}

OC_FILES.getSelected=function(){
    var nodes=document.getElementsByName('fileSelector');
    var files=Array();
    for(var index=0;index<nodes.length;index++){
        if(nodes[index].checked){
            files[files.length]=nodes[index].value;
        }
    }
    return files;
}

OC_FILES.newFile=function(type,name,dir){
	arg=new Object;
	arg.name=name;
	arg.dir=dir;
	arg.type=type;
	OC_API.run('new',{dir:dir,name:name,type:type},OC_FILES.new_callback,arg)
	if(!OC_FILES.cache.incomplete[dir]){
		OC_FILES.cache.incomplete[dir]=Array();
	}
	OC_FILES.cache.incomplete[dir][name]=Array();
	OC_FILES.cache.incomplete[dir][name]['name']=name;
	OC_FILES.cache.incomplete[dir][name]['type']='incomplete';
	OC_FILES.cache.incomplete[dir][name]['size']=0;
	OC_FILES.browser.files.add(name,'incomplete');
}

OC_FILES.new_callback=function(req,file){
	OC_FILES.cache.files[file.name]=OC_FILES.cache.incomplete[file.dir][file.name];
	delete OC_FILES.cache.incomplete[file.dir][file.name];
	OC_FILES.cache.files[file.name]['type']=file.type;
	OC_FILES.browser.files.remove(file.name);
// 	OC_FILES.browser.files.add(name);
	OC_FILES.browser.show(OC_FILES.dir,true);
}

OC_FILES.move=function(source,target,sourceDir,targetDir){
	if(sourceDir!=targetDir || source!=target){
		if(!OC_FILES.cache.incomplete[sourceDir]){
			OC_FILES.cache.incomplete[sourceDir]=Array();
		}
		if(!OC_FILES.cache.incomplete[targetDir]){
			OC_FILES.cache.incomplete[targetDir]=Array();
		}
		if(!OC_FILES.cache.incomplete[targetDir+'/'+target]){
			OC_FILES.cache.incomplete[targetDir+'/'+target]=Array();
		}
		arg=new Object;
		arg.source=source;
		arg.target=target;
		arg.sourceDir=sourceDir;
		arg.targetDir=targetDir;
		arg.type=OC_FILES.cache.files[source]['type'];
		OC_FILES.cache.files[source]['type']='incomplete';
		OC_FILES.cache.incomplete[targetDir+'/'+target][source]=OC_FILES.cache.files[source];
		OC_API.run('move',{sourcedir:sourceDir,source:source,targetdir:targetDir,target:target},OC_FILES.move_callback,arg);
	}
}

OC_FILES.move_callback=function(req,file){
	OC_FILES.cache.incomplete[file.targetDir+'/'+file.target][file.source]['type']=file.type;
	delete OC_FILES.cache.files[file.source];
	OC_FILES.browser.show(OC_FILES.dir);
}

OC_FILES.selectAll=function(){
    var value=document.getElementById('select_all').checked;
    var nodes=document.getElementsByName('fileSelector');
    for(var index=0;index<nodes.length;index++){
        if(nodes[index].value){
            nodes[index].checked=value;
        }
    }
}

OC_FILES.action_selected=function(){
    var dropdown=action=document.getElementById('selected_action');
    var action=dropdown.options[dropdown.selectedIndex].value;
    if(OC_FILES.actions_selected[action] && OC_FILES.actions_selected[action].call){
        OC_FILES.actions_selected[action].call(OC_FILES);
    }
}

OC_FILES.actions_selected=new Object();

OC_FILES.actions_selected.download=function(){
    files=OC_FILES.getSelected();
    if(files.length==0){
        return false;
    }else if(files.length>1){
        files=files.join(';');
    }else{
        files=files[0];
    }
    OC_FILES.get(dir,files);
}

OC_FILES.actions_selected['delete']=function(){
    files=OC_FILES.getSelected();
    for(index in files){
        OC_FILES.remove(OC_FILES.dir,files[index]);
    }
}

OC_FILES.files=Array();

OC_FILES.file=function(dir,file,type,mime){
	if(file){
		this.type=type;
		this.file=file;
		this.dir=dir;
		this.mime=mime;
		if(mime){
			var mimeParts=mime.split('/');
			this.mime1=mimeParts[0];
			this.mime2=mimeParts[1];
		}
		this.actions=new Object();
		if(file.lastIndexOf('.')){
			this.extention=file.substr(file.lastIndexOf('.')+1);
		}else{
			this.extention;
		}
		for(index in OC_FILES.fileActions.all){
			if(OC_FILES.fileActions.all[index].call){
				this.actions[index]=OC_FILES.fileActions.all[index];
			}
		}
		if(OC_FILES.fileActions[this.type]){
			for(index in OC_FILES.fileActions[this.type]){
				if(OC_FILES.fileActions[this.type][index].call){
					this.actions[index]=OC_FILES.fileActions[this.type][index];
				}
			}
		}
		if(OC_FILES.fileActions[this.mime1]){
			for(index in OC_FILES.fileActions[this.mime1]){
				if(OC_FILES.fileActions[this.mime1][index].call){
					this.actions[index]=OC_FILES.fileActions[this.mime1][index];
				}
			}
		}
		if(OC_FILES.fileActions[this.mime]){
			for(index in OC_FILES.fileActions[this.mime]){
				if(OC_FILES.fileActions[this.mime][index].call){
					this.actions[index]=OC_FILES.fileActions[this.mime][index];
				}
			}
		}
	}
}

OC_FILES.file.prototype.showactions=function(){
    OC_FILES.browser.showactions(this.file);
}

OC_FILES.file.prototype.hideactions=function(){
    OC_FILES.browser.showactions(this.file,true);
}

OC_FILES.fileActions=new Object();

OC_FILES.fileActions.all=new Object();

OC_FILES.fileActions.all.remove=function(){
    OC_FILES.remove(this.dir,this.file);
}
OC_FILES.fileActions.all.rename=function(){
    OC_FILES.browser.show_rename(this.dir,this.file);
}
OC_FILES.fileActions.all.download=function(){
	OC_FILES.get(this.dir,this.file);
}
OC_FILES.fileActions.all['default']=OC_FILES.fileActions.all.download;

OC_FILES.fileActions.dir=new Object()

OC_FILES.fileActions.dir.open=function(){
    OC_FILES.browser.show(this.dir+'/'+this.file);
}
OC_FILES.fileActions.dir['default']=OC_FILES.fileActions.dir.open;

OC_FILES.fileActions.dir.dropOn=function(file){
	OC_FILES.move(file.file,file.file,file.dir,this.dir+'/'+this.file);
}

OC_FILES.fileActions.image=new Object()

OC_FILES.fileActions.image.show=function(){
	OC_FILES.browser.showImage(this.dir,this.file);
}

OC_FILES.fileActions.image['default']=OC_FILES.fileActions.image.show;
