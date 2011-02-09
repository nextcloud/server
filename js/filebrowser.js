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
* You should have received a copy of the GNU Affero General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

OC_FILES.browser=new  Object();

OC_FILES.browser.showInitial=function(){
	if(document.getElementById('content')){
		var dir=''
		var loc=document.location.toString();
		if(loc.indexOf('#')!=-1){
			dir=loc.substring(loc.indexOf('#')+1);
		}
		OC_FILES.getdirectorycontent(dir,OC_FILES.browser.show_callback,true);
	}
}

OC_FILES.browser.show=function(dir,forceReload){
   if(!dir || !dir.split){
      dir='';
   }
   OC_FILES.getdirectorycontent(dir,OC_FILES.browser.show_callback,forceReload);
}

OC_FILES.browser.breadcrumb=new Object();
OC_FILES.browser.breadcrumb.node=null;
OC_FILES.browser.breadcrumb.crumbs=Array();
OC_FILES.browser.breadcrumb.show=function(parent,path){
	if((!OC_FILES.browser.breadcrumb.node==parent && parent) || OC_FILES.browser.breadcrumb.node==null){
		OC_FILES.browser.breadcrumb.clear();
		OC_FILES.browser.breadcrumb.node=parent;
		OC_FILES.browser.breadcrumb.add('Home','/');
	}
	var dirs=path.split('/');
	var currentPath='/';
	var paths=Array();
	var currentPath;
	if(dirs.length>0){
		for(var i=0;i<dirs.length;i++){
			dir=dirs[i];
			if(dir){
				currentPath+=dir+'/';
				paths[currentPath]=true;
				if(!OC_FILES.browser.breadcrumb.crumbs[currentPath]){
					OC_FILES.browser.breadcrumb.add(dir,currentPath);
				}
			}
		}
	}
	//remove all crumbs that are not part of our current path
	for(currentPath in OC_FILES.browser.breadcrumb.crumbs){
		if(!paths[currentPath] && currentPath!='/'){
			OC_FILES.browser.breadcrumb.remove(currentPath);
		}
	}
	
}
OC_FILES.browser.breadcrumb.add=function(name,path){
	var a=document.createElement('a');
	var div=document.createElement('div');
	OC_FILES.browser.breadcrumb.crumbs[path]=div;
	div.className='breadcrumb';
	a.setAttribute('href','#'+path);
	a.addEvent('onclick',OC_FILES.browser.show,path);
	img=document.createElement('img');
	img.src=WEBROOT+'/img/arrow.png';
	a.appendChild(document.createTextNode(' ' +name));
	a.appendChild(img);
	OC_FILES.files[path]=new OC_FILES.file('',path,'dir');
	div.makeDropTarget();
	div.file=OC_FILES.files[path];
	div.addEvent('ondropon',OC_FILES.browser.handleDropOn);
	div.appendChild(a);
	
	OC_FILES.browser.breadcrumb.node.appendChild(div);
}
OC_FILES.browser.breadcrumb.remove=function(path){
	if(OC_FILES.browser.breadcrumb.crumbs[path]){
		var div=OC_FILES.browser.breadcrumb.crumbs[path];
		if(div.parentNode){
			div.parentNode.removeChild(div);
		}
		delete OC_FILES.browser.breadcrumb.crumbs[path];
	}
}
OC_FILES.browser.breadcrumb.clear=function(){
	for(path in OC_FILES.browser.breadcrumb.crumbs){
		OC_FILES.browser.breadcrumb.remove(path);
	}
}

OC_FILES.browser.files=new Object();
OC_FILES.browser.files.fileNodes=Array();
OC_FILES.browser.files.node=null;
OC_FILES.browser.files.tbody=null;
OC_FILES.browser.files.show=function(parent,fileList){
	if(parent){
		OC_FILES.browser.files.node=parent;
	}
	var table=document.createElement('table');
	OC_FILES.browser.files.node.appendChild(table);
	var tbody=document.createElement('tbody');
	OC_FILES.browser.files.tbody=tbody;
	table.appendChild(tbody);
	table.setAttribute('cellpadding',6);
	table.setAttribute('cellspacing',0);
	if(fileList){
		var name;
		//remove files that no longer are in the folder
		for(name in OC_FILES.browser.files.fileNodes){
			if(!fileList[name]){
				OC_FILES.browser.files.remove(name);
			}
		}
		//add the files that arent in the list yet
		var unreadableFiles=[];
		for(name in fileList){
			file=fileList[name];
			if(file.readable){
				if(!OC_FILES.browser.files.fileNodes[file.name]){
					OC_FILES.browser.files.add(file.name,file.type,file.size,file.date,file.mime);
				}
			}else if(file.name){
				unreadableFiles.push(file);
			}
		}
		if(unreadableFiles.length>0){
			var message=unreadableFiles.length+" unreadable files detected:\n";
			var first=true;
			unreadableFiles.foreach(function(item){
				if(!first){
					message+=', ';
				}
				first=false;
				message+=item.name;
			});
			message+="\nPlease check the file premissions";
			alert(message);
		}
	}
}
OC_FILES.browser.files.add=function(name,type,size,date,mime){
	if(name){
		if(!size) size=0;
		if(!date) date=getTimeString();
		OC_FILES.files[name]=new OC_FILES.file(OC_FILES.dir,name,type,mime);
		tr=document.createElement('tr');
		OC_FILES.browser.files.fileNodes[name]=tr;
		OC_FILES.browser.files.tbody.appendChild(tr);
		tr.className='browserline';
		td=document.createElement('td');
		tr.appendChild(td);
		td.className='fileSelector';
		input=document.createElement('input');
		input.setAttribute('type','checkbox');
		input.setAttribute('name','fileSelector');
		input.setAttribute('value',name);
		td.appendChild(input);
		tr.appendChild(OC_FILES.browser.showicon(type));
		td=document.createElement('td');
		tr.appendChild(td);
		td.makeDropTarget();
		td.addEvent('ondropon',OC_FILES.browser.handleDropOn);
		td.className='nametext';
		td.setAttribute('name',name);
		td.setAttribute('id',name);
		var fileObject=OC_FILES.files[name];
		td.file=fileObject;
		a=document.createElement('a');
		td.appendChild(a);
		a.appendChild(document.createTextNode(name));
		a.addEvent('onclick',fileObject.actions['default'].bindScope(fileObject));
		a.makeDraggable();
		a.addEvent('ondrop',OC_FILES.browser.handleDrop);
		if(type=='dir'){
			td.setAttribute('colspan',2);
			var dirname=name;
			if(OC_FILES.dir[OC_FILES.dir.length-1]!='/'){
				dirname='/'+name;
			}
			a.setAttribute('href','#'+OC_FILES.dir+dirname);
		}else{
			a.setAttribute('href','#'+OC_FILES.dir);
			if(!SMALLSCREEN){
				sizeTd=document.createElement('td');
				tr.appendChild(sizeTd);
				sizeTd.className='sizetext';
				sizeTd.appendChild(document.createTextNode(sizeFormat(size)));
			}else{
				td.setAttribute('colspan',2);
			}
		}
		a=document.createElement('a');
		var img=document.createElement('img');
		td.appendChild(img);
		img.className='file_actions';
		img.alt=''
		img.title='actions';
		img.src=WEBROOT+'/img/arrow_down.png';
		img.addEvent('onclick',OC_FILES.browser.showactions.bind(name));
		if(!SMALLSCREEN){
			td=document.createElement('td');
			tr.appendChild(td);
			td.className='sizetext';
			td.appendChild(document.createTextNode(date));
		}
	}
}

OC_FILES.browser.files.remove=function(name){
	if(OC_FILES.browser.files.fileNodes[name]){
		tr=OC_FILES.browser.files.fileNodes[name];
		tr.parentNode.removeChild(tr);
		delete OC_FILES.browser.files.fileNodes[name];
	}
	
}
OC_FILES.browser.files.clear=function(){
	for(name in OC_FILES.browser.files.fileNodes){
		OC_FILES.browser.files.remove(name);
	}
}

OC_FILES.browser.table=null;
OC_FILES.browser.show_callback=function(content){
	var dir=OC_FILES.dir
	var tr=null;
	var td=null;
	var img=null;
	if(!OC_FILES.browser.table){
		body=document.getElementsByTagName('body').item(0);
		body.addEvent('onclick',OC_FILES.browser.hideallactions);
		
		//remove current content;
		var contentNode=document.getElementById('content');
		contentNode.className='center';
		if(contentNode.hasChildNodes()){
			while(contentNode.childNodes.length >=1){
				contentNode.removeChild(contentNode.firstChild);
			}
		}
		var table=document.createElement('table');
		OC_FILES.browser.table=table;
		table.className='browser';
		var tbody=document.createElement('tbody');
		var thead=document.createElement('thead');
		var tfoot=document.createElement('tfoot');
		table.appendChild(thead);
		table.appendChild(tbody);
		table.appendChild(tfoot);
		OC_FILES.files=Array();
		table.setAttribute('cellpadding',6);
		
		tr=document.createElement('tr');
		thead.appendChild(tr);
		tr.className='breadcrumb';
		td=document.createElement('td');
		tr.appendChild(td);
		input=document.createElement('input');
		input.className='fileSelector'
		input.setAttribute('type','checkbox');
		input.setAttribute('name','fileSelector');
		input.setAttribute('value','select_all');
		input.setAttribute('id','select_all');
		input.addEvent('onclick',OC_FILES.selectAll);
		td.appendChild(input);
		td.className='breadcrumb';
		OC_FILES.browser.breadcrumb.show(td,dir);
		// files and directories
		tr=document.createElement('tr');
		tbody.appendChild(tr);
		td=document.createElement('td');
		tr.appendChild(td);
		div=document.createElement('div');
		div.className='fileList';
		td.appendChild(div);
		OC_FILES.browser.files.show(div,content);
		tr=document.createElement('tr');
		tfoot.appendChild(tr);
		tr.className='utilityline';
		td=document.createElement('td');
		tr.appendChild(td);
		td.className='actionsSelected';
		dropdown=document.createElement('select');
		td.appendChild(dropdown);
		dropdown.setAttribute('id','selected_action');
		for(index in OC_FILES.actions_selected){
			if(OC_FILES.actions_selected[index].call){
				option=document.createElement('option');
				dropdown.appendChild(option);
				option.setAttribute('value',index);
				option.appendChild(document.createTextNode(capitaliseFirstLetter(index)));
			}
		}
		td.appendChild(document.createTextNode(' Selected '));
		button=document.createElement('button');
		td.appendChild(button);
		button.appendChild(document.createTextNode('Go'));
		button.addEvent('onclick',OC_FILES.action_selected);
		div=document.createElement('div');
		td.appendChild(div);
		div.className='moreActionsButton';
		OC_FILES.maxUpload=content['max_upload'];
		var p=document.createElement('p');
		div.appendChild(p);
		p.appendChild(document.createTextNode('More Actions'));
		div.setAttribute('id','moreActionsButton');
		OC_FILES.browser.moreActionsShown=false;
		p.addEvent('onclick',OC_FILES.browser.showMoreActions);
		contentNode.appendChild(table);
	}else{
		OC_FILES.browser.breadcrumb.show(null,dir);
		OC_FILES.browser.files.show(null,content);
	}
	if(OC_FILES.uploadForm){
		OC_FILES.uploadForm.setAttribute('action','files/upload.php?dir='+encodeURIComponent(dir));
	}
}

OC_FILES.browser.handleDropOn=function(event,node){
	var dropTargetFile=this.file;
	var dropFile=node.parentNode.file;
	if(dropTargetFile!=dropFile){
		if(dropTargetFile.actions.dropOn && dropTargetFile.actions.dropOn.call){
			dropTargetFile.actions.dropOn.call(dropTargetFile,dropFile);
		}
		return false;
	}
}

OC_FILES.browser.handleDrop=function(event,node){
	var dropTargetFile=node.file;
	var dropFile=this.parentNode.file;
	if(dropFile.actions.drop && dropFile.actions.drop.call){
		dropFile.actions.drop.call(dropFile,dropTargetFile);
	}
	return false;
}

OC_FILES.browser.showMoreActions=function(){
	if(!OC_FILES.browser.moreActionsList){
		var div=document.createElement('div');
		div.className='moreActionsList';
		var table=document.createElement('table');
		div.appendChild(table);
		var tbody=document.createElement('tbody');
		table.appendChild(tbody);
		var tr=document.createElement('tr');
		tbody.appendChild(tr);
		var td=document.createElement('td');
		tr.appendChild(td);
		OC_FILES.browser.showuploader(OC_FILES.dir,td,OC_FILES.maxUpload);
		tr=document.createElement('tr');
		tbody.appendChild(tr);
		td=document.createElement('td');
		tr.appendChild(td);
		var form=document.createElement('form');
		td.appendChild(form);
		form.appendChild(document.createTextNode('New '));
		var dropdown=document.createElement('select');
		form.appendChild(dropdown);
		dropdown.setAttribute('id','newFileType');
		var option=document.createElement('option');
		dropdown.appendChild(option);
		option.setAttribute('value','dir');
		option.appendChild(document.createTextNode('Folder'));
		option=document.createElement('option');
		dropdown.appendChild(option);
		option.setAttribute('value','file');
		option.appendChild(document.createTextNode('File'));
		form.appendChild(document.createTextNode(' '));
		var input=document.createElement('input');
		form.appendChild(input);
		input.setAttribute('id','newFileName');
		form.addEvent('onsubmit',OC_FILES.browser.newFile);
		var submit=document.createElement('input');
		submit.type='submit';
		form.appendChild(submit);
		submit.value='Create';
		OC_FILES.browser.moreActionsList=div;
	}else{
		var div=OC_FILES.browser.moreActionsList;
	}
	var button=document.getElementById('moreActionsButton');
	if(!OC_FILES.browser.moreActionsShown){
		button.appendChild(div);
		OC_FILES.browser.moreActionsShown=true;
		button.className='moreActionsButton moreActionsButtonClicked';
	}else{
		OC_FILES.browser.moreActionsShown=false;
		button.removeChild(div);
		button.className='moreActionsButton';
	}
}

OC_FILES.browser.newFile=function(event){
	if(event.preventDefault){
		event.preventDefault();
	};
	var typeSelect=document.getElementById('newFileType');
	var type=typeSelect.options[typeSelect.selectedIndex].value;
	var name=document.getElementById('newFileName').value;
	OC_FILES.newFile(type,name,OC_FILES.dir);
	return false;
}

OC_FILES.browser.showicon=function(filetype){
   var td=document.createElement('td');
   td.className='fileicon';
   var img=document.createElement('img');
   td.appendChild(img);
   img.setAttribute('width',16);
   img.setAttribute('height',16);
   if(filetype=='dir'){
      img.src=WEBROOT+'/img/icons/folder.png';
   }else if(filetype=='incomplete'){
      img.src=WEBROOT+'/img/icons/loading.gif';
   }else{
      img.src=WEBROOT+'/img/icons/other.png';
   }
   return td;
}

OC_FILES.uploadIFrames=Array();
OC_FILES.browser.showuploader=function(dir,parent,max_upload){
	var iframeId=OC_FILES.uploadIFrames.length
	OC_FILES.uploadForm=document.createElement('form');
	OC_FILES.uploadForm.setAttribute('target','uploadIFrame'+iframeId);
	OC_FILES.uploadForm.setAttribute('action','files/upload.php?dir='+encodeURIComponent(dir));
	OC_FILES.uploadForm.method='post';
	OC_FILES.uploadForm.setAttribute('enctype','multipart/form-data');
	OC_FILES.uploadIFrames[iframeId]=document.createElement('iframe');
	OC_FILES.uploadIFrames[iframeId].uploadParent=parent;
	OC_FILES.uploadIFrames[iframeId].className='hidden';
	OC_FILES.uploadIFrames[iframeId].name='uploadIFrame'+iframeId;
	var input=document.createElement('input');
	input.setAttribute('type','hidden');
	input.setAttribute('name','MAX_FILE_SIZE');
	input.setAttribute('value',max_upload);
	input.setAttribute('id','max_upload');
	OC_FILES.uploadForm.appendChild(input);
	var file=document.createElement('input');
	file.name='file';
	file.setAttribute('id','fileSelector');
	file.setAttribute('type','file');
	file.addEvent('onchange',OC_FILES.upload.bind(iframeId));
	OC_FILES.uploadForm.appendChild(document.createTextNode('Upload file: '));
	OC_FILES.uploadForm.appendChild(file);
	parent.appendChild(OC_FILES.uploadForm);
	var body=document.getElementsByTagName('body').item(0);
	body.appendChild(OC_FILES.uploadIFrames[iframeId]);
}

OC_FILES.browser.show_rename=function(dir,file){
   var item=document.getElementById(file);
   item.oldContent=Array();
   if(item.hasChildNodes()){
      while(item.childNodes.length >=1){
         item.oldContent[item.oldContent.length]=item.firstChild;
         item.removeChild(item.firstChild);
      }
   }
   var form=document.createElement('form');
   form.addEvent('onsubmit',OC_FILES.rename.bind(dir).bind(file));
   var input=document.createElement('input');
   input.setAttribute('type','text');
   input.setAttribute('name','newname');
   input.setAttribute('value',file);
   input.setAttribute('id',file+'_newname')
   input.addEvent('onblur',OC_FILES.browser.rename_cancel.bind(file));
   form.appendChild(input);
   item.appendChild(form);
   input.focus();
}

OC_FILES.browser.rename_cancel=function(file){
   var item=document.getElementsByName(file).item(0);
   if(item.hasChildNodes()){
      while(item.childNodes.length >=1){
         item.removeChild(item.firstChild);
      }
   }
   for(index in item.oldContent){
      if(item.oldContent[index].nodeType){
         item.appendChild(item.oldContent[index]);
      }
   }
}

OC_FILES.browser.showactions=function(file,hide){
    var node=document.getElementById(file);
    if(node &&(node.actionsshown || hide===true)){
        if(node.actionsshown){
            node.actionsdiv.parentNode.removeChild(node.actionsdiv);
        }
        node.actionsdiv=null;
        node.actionsshown=false
    }else if(node){
        node.actionsshown=true
        div=document.createElement('div');
        node.actionsdiv=div;
        div.className='fileactionlist';
        table=document.createElement('table');
        div.appendChild(table);
        tbody=document.createElement('tbody');
        table.appendChild(tbody);
        var file=OC_FILES.files[file]
        var actions=file.actions;
        var name;
        for(name in actions){
            if(actions[name].call && name!='default' && name!='dropOn' && name!='drop'){
                tr=document.createElement('tr');
                tbody.appendChild(tr);
                td=document.createElement('td');
                tr.appendChild(td);
                a=document.createElement('a');
                td.appendChild(a);
                a.appendChild(document.createTextNode(capitaliseFirstLetter(name)));
                var action=actions[name];
                td.addEvent('onclick',action.bindScope(file));
            }
        }
        node.appendChild(div);
        OC_FILES.hideallenabled=false;
        setTimeout('OC_FILES.hideallenabled=true',50);
    }
}

OC_FILES.browser.hideallactions=function(){
    if(OC_FILES.hideallenabled){
        for(name in OC_FILES.files){
			if(OC_FILES.files[name]){
				if(OC_FILES.files[name].hideactions){
					OC_FILES.files[name].hideactions.call(OC_FILES.files[name]);
				}
			}
        }
    }
}

OC_FILES.hideallenabled=true; //used to prevent browsers from hiding actionslists right after they are displayed;

sizeFormat=function(size){
	if(isNaN(size)){
		return false;
	}
	var orig=size;
	var steps=Array('B','KiB','MiB','GiB','TiB');
	var step=0;
	while(size>(1024*2)){
		step++;
		size=size/1024;
	}
	if(size.toFixed){
		size=size.toFixed(2);
	}
	return ''+size+' '+steps[step];
}

OC_FILES.browser.showImage=function(dir,file){
	var path=WEBROOT+'/files/open_file.php?dir='+encodeURIComponent(dir)+'&file='+encodeURIComponent(file);
	var div=document.createElement('div');
	div.setAttribute('id','imageframe');
	div.addEvent('onclick',OC_FILES.browser.hideImage)
	var img=document.createElement('img');
	img.setAttribute('src',path);
	div.appendChild(img);
	body=document.getElementsByTagName('body').item(0);
	body.appendChild(div);
}

OC_FILES.browser.hideImage=function(){
	var div=document.getElementById('imageframe');
	div.parentNode.removeChild(div);
}

function capitaliseFirstLetter(string){
	return string.charAt(0).toUpperCase() + string.slice(1);
}