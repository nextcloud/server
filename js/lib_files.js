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
OC_FILES.xmlloader=new OCXMLLoader();

OC_FILES.getdirectorycontent_parse=function(req){
   var files=new Array();
   var response=req.responseXML;
   if(response){
       var dir=response.getElementsByTagName('dir').item(0);
       files['max_upload']=dir.getAttribute('max_upload');
       var fileElements=response.getElementsByTagName('file');
       if(fileElements.length>0){
          for(index=0;index<fileElements.length;index++){
//           for(index in fileElements){
             var file=new Array();
             var attributes=Array('size','name','type','directory','date');
             for(i in attributes){
                var name=attributes[i];
                file[name]=fileElements.item(index).getAttribute(name);
             }
             files[file.name]=file;
          }
       }
       if(OC_FILES.getdirectorycontent_callback){
          OC_FILES.getdirectorycontent_callback(files);
       }
   }
}

OC_FILES.getdirectorycontent=function(dir,callback){
   if(callback){
      OC_FILES.getdirectorycontent_callback=callback;
   }
   OC_FILES.xmlloader.setCallBack(OC_FILES.getdirectorycontent_parse);
   OC_FILES.xmlloader.load('files/get_files.php?dir='+dir);
}

OC_FILES.dir='';

OC_FILES.upload=function(dir){
   OC_FILES.uploadIFrame.addEvent('onload',new callBack(OC_FILES.upload_callback,OC_FILES),dir);
   var fileSelector=document.getElementById('fileSelector');
   var max_upload=document.getElementById('max_upload').value;
   if(fileSelector.files && fileSelector.files[0].fileSize){
       var size=fileSelector.files[0].fileSize;
       if(size>max_upload){
           new OCNotification('File to large',10000)
           return false;
       }
   }
   OC_FILES.uploadForm.submit();
}

OC_FILES.upload_callback=function(dir){
   this.browser.show(dir);
}

OC_FILES.rename=function(dir,file){
   var item=document.getElementById(file+'_newname');
   var newname=item.value;
   if(newname==''){
      return false;
   }else if(file==newname){
      OC_FILES.browser.show(OC_FILES.dir);
      return false;
   }
   xmlloader=new OCXMLLoader();
   xmlloader.setCallBack(OC_FILES.rename_callback);
   xmlloader.load('files/rename.php?dir='+dir+'&file='+file+'&newname='+newname);
   return false;
}


OC_FILES.rename_callback=function(req){
   OC_FILES.browser.show(OC_FILES.dir);
}

OC_FILES.remove=function(dir,file){
   remove=confirm('remove file \''+file+'\'?');
   if(remove){
      xmlloader=new OCXMLLoader();
      xmlloader.setCallBack(OC_FILES.remove_callback);
      xmlloader.load('files/delete.php?dir='+dir+'&file='+file);
   }
}

OC_FILES.remove_callback=function(req){
   OC_FILES.browser.show(OC_FILES.dir);
}

OC_FILES.getSelected=function(){
    var nodes=document.getElementsByName('fileSelector');
    var files=Array();
    for(index in nodes){
        if(nodes[index].checked){
            files[files.length]=nodes[index].value;
        }
    }
    return files;
}

OC_FILES.selectAll=function(){
    var value=document.getElementById('select_all').checked;
    var nodes=document.getElementsByName('fileSelector');
    for(index in nodes){
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
        files.join(';');
    }else{
        files=files[0];
    }
    window.location=WEBROOT+'/files/get_file.php?dir='+OC_FILES.dir+'&files='+files;
}

OC_FILES.actions_selected['delete']=function(){
    files=OC_FILES.getSelected();
    for(index in files){
        OC_FILES.remove(OC_FILES.dir,files[index]);
    }
}

OC_FILES.files=Array();

OC_FILES.file=function(dir,file,type){
    this.type=type;
    this.file=file;
    this.dir=dir;
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
	if(OC_FILES.fileActions[this.extention]){
		for(index in OC_FILES.fileActions[this.extention]){
			if(OC_FILES.fileActions[this.extention][index].call){
				this.actions[index]=OC_FILES.fileActions[this.extention][index];
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
    window.location=WEBROOT+'/files/get_file.php?dir='+this.dir+'&files='+this.file;
}
OC_FILES.fileActions.all['default']=OC_FILES.fileActions.all.download;

OC_FILES.fileActions.dir=new Object()

OC_FILES.fileActions.dir.open=function(){
    OC_FILES.browser.show(this.dir+'/'+this.file);
}
OC_FILES.fileActions.dir['default']=OC_FILES.fileActions.dir.open;

OC_FILES.fileActions.jpg=new Object()

OC_FILES.fileActions.jpg.show=function(){
//     window.open(WEBROOT+'/files/open_file.php?dir='+this.dir+'&file='+this.file);
	OC_FILES.browser.showImage(this.dir,this.file);
}

OC_FILES.fileActions.jpg['default']=OC_FILES.fileActions.jpg.show;

OC_FILES.fileActions.jpeg=OC_FILES.fileActions.jpg
OC_FILES.fileActions.png=OC_FILES.fileActions.jpg
OC_FILES.fileActions.gif=OC_FILES.fileActions.jpg
OC_FILES.fileActions.bmp=OC_FILES.fileActions.jpg

function getStyle(el,styleProp)
{
// 	var x = document.getElementById(el);
	var x=el;
	if (x.currentStyle){
		alert(x.currentStyle);
		var y = x.currentStyle[styleProp];
	}else if (window.getComputedStyle){
		var y = document.defaultView.getComputedStyle(x,null).getPropertyValue(styleProp);
	}
	return y;
}

Node.prototype.getStyle=function(styleProp){
	return getStyle(this,styleProp)
}