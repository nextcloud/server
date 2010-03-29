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

OC_FILES.showicon=function(filetype){
   var td=document.createElement('td');
   var img=document.createElement('img');
   td.appendChild(img);
   img.setAttribute('width',16);
   img.setAttribute('height',16);
   if(filetype=='dir'){
      img.src=WEBROOT+'/img/icons/folder.png';
   }else{
      img.src=WEBROOT+'/img/icons/other.png';
   }
   return td;
}

OC_FILES.dir='';
OC_FILES.browser=null;
OC_FILES.showbrowser=function(dir){
   dir=(dir)?dir:'';
   OC_FILES.dir=dir;
   OC_FILES.getdirectorycontent(dir,OC_FILES.showbrowser_callback);
}

OC_FILES.showbrowser_callback=function(content){
    var dir=OC_FILES.dir
    var dirs=dir.split('/');
    var tr=null;
    var td=null;
    var img=null;
    
    body=document.getElementsByTagName('body').item(0);
    body.setAttribute('onclick',body.getAttribute('onclick')+' ; OC_FILES.hideallactions()');
    
    //remove current content;
    var contentNode=document.getElementById('content');
    if(contentNode.hasChildNodes()){
       while(contentNode.childNodes.length >=1){
          contentNode.removeChild(contentNode.firstChild);
       }
    }
    
    var browser=document.createElement('div');
    browser.className='center';
    var table=document.createElement('table');
    browser.appendChild(table);
    
    // breadcrumb
    if(dirs.length>0) {
       table.setAttribute('cellpadding',2);
       table.setAttribute('cellspacing',0);
       var tbody=document.createElement('tbody');//some IE versions need this
       table.appendChild(tbody);
       tr=document.createElement('tr');
       tbody.appendChild(tr);
       td=document.createElement('td');
       tr.appendChild(td);
       td.setAttribute('colspan','6');
       td.className='breadcrumb';
       var a=document.createElement('a');
       td.appendChild(a);
       a.setAttribute('href','#');
       a.setAttribute('onclick','OC_FILES.showbrowser()');
       a.appendChild(document.createTextNode('Home'));
       var currentdir='';
       for(index in dirs) {
          d=dirs[index];
          currentdir+='/'+d;
          if(d!=''){
//              td=document.createElement('td');
//              tr.appendChild(td);
//              td.className='breadcrumb';
             a=document.createElement('a');
             td.appendChild(a);
             a.setAttribute('href','#'+currentdir);
             a.setAttribute('onclick','OC_FILES.showbrowser("'+currentdir+'")');
             img=document.createElement('img');
             a.appendChild(img);
             img.src=WEBROOT+'/img/arrow.png';
             a.appendChild(document.createTextNode(' ' +d));
          }
      }
    }

    // files and directories
    table.setAttribute('cellpadding',6);
    table.setAttribute('cellspacing',0);
    table.className='browser';
    var tbody=document.createElement('tbody');//some IE versions need this
    table.appendChild(tbody);
    var filesfound=false;
    var sizeTd=null;
    if(content){
         tr=document.createElement('tr');
         tbody.appendChild(tr);
         tr.className='browserline';
         td=document.createElement('td');
         tr.appendChild(td);
         td.setAttribute('colspan','2');
         input=document.createElement('input');
         input.setAttribute('type','checkbox');
         input.setAttribute('name','fileSelector');
         input.setAttribute('value','select_all');
         input.setAttribute('id','select_all');
         input.setAttribute('onclick','OC_FILES.selectAll()');
         td.appendChild(input);
         td=document.createElement('td');
         tr.appendChild(td);
         td.setAttribute('colspan','4');
         dropdown=document.createElement('select');
         td.appendChild(dropdown);
         dropdown.setAttribute('id','selected_action');
         for(index in this.actions_selected){
            if(this.actions_selected[index].call){
                option=document.createElement('option');
                dropdown.appendChild(option);
                option.setAttribute('value',index);
                option.appendChild(document.createTextNode(index));
            }
         }
         td.appendChild(document.createTextNode(' selected. '));
         button=document.createElement('button');
         td.appendChild(button);
         button.appendChild(document.createTextNode('Go'));
         button.setAttribute('onclick','OC_FILES.action_selected()');
         for(index in content){
          file=content[index];
          if(file.name){
             file.name=file.name.replace('\'','');
             OC_FILES.files[file['name']]=new OC_FILES.file(dir,file['name'],file['type']);
             tr=document.createElement('tr');
             tbody.appendChild(tr);
             tr.className='browserline';
             td=document.createElement('td');
             tr.appendChild(td);
             input=document.createElement('input');
             input.setAttribute('type','checkbox');
             input.setAttribute('name','fileSelector');
             input.setAttribute('value',file['name']);
             td.appendChild(input);
             tr.appendChild(OC_FILES.showicon(file['type']));
             td=document.createElement('td');
             tr.appendChild(td);
             td.className='nametext';
             td.setAttribute('name',file['name']);
             td.setAttribute('id',file['name']);
             a=document.createElement('a');
             td.appendChild(a);
             a.appendChild(document.createTextNode(file['name']))
             if(file['type']=='dir'){
                a.setAttribute('onclick','OC_FILES.showbrowser("'+dir+file['name']+'")');
                td.setAttribute('colspan',2);
                a.setAttribute('href','#'+dir+file['name']);
             }else{
                a.setAttribute('href',WEBROOT+'/?dir=/'+dir+'&file='+file['name']);
                sizeTd=document.createElement('td');
                tr.appendChild(sizeTd);
                sizeTd.className='sizetext';
                sizeTd.appendChild(document.createTextNode(sizeFormat(file['size'])));
             }
             a=document.createElement('a');
             img=document.createElement('img');
             td.appendChild(img);
             img.className='file_actions';
             img.alt=''
             img.title='actions';
             img.src=WEBROOT+'/img/arrow_down.png';
             img.setAttribute('onclick','OC_FILES.showactions(\''+file['name']+'\')')
             td=document.createElement('td');
             tr.appendChild(td);
             td.className='sizetext';
             td.appendChild(document.createTextNode(file['date']));
          }
       }
    }
    td=document.createElement('td');
    tr.appendChild(td);
    tr=document.createElement('tr');
    tbody.appendChild(tr);
    tr.className='utilrow';
    td=document.createElement('td');
    tr.appendChild(td);
    td.className='upload';
    td.setAttribute('colspan','6');
    this.showuploader(dir,td,content['max_upload']);
    contentNode.appendChild(browser);
}

OC_FILES.showuploader=function(dir,parent,max_upload){
   this.uploadForm=document.createElement('form');
   this.uploadForm.setAttribute('target','uploadIFrame');
   this.uploadForm.setAttribute('action','files/upload.php?dir='+dir);
   this.uploadForm.method='post';
   this.uploadForm.setAttribute('enctype','multipart/form-data');
   this.uploadIFrame=document.createElement('iframe');
   this.uploadIFrame.className='hidden';
   this.uploadIFrame.name='uploadIFrame';
   parent.appendChild(this.uploadIFrame);
   var input=document.createElement('input');
   input.setAttribute('type','hidden');
   input.setAttribute('name','MAX_FILE_SIZE');
   input.setAttribute('value',max_upload);
   input.setAttribute('id','max_upload');
   this.uploadForm.appendChild(input);
   var file=document.createElement('input');
   file.name='file';
   file.setAttribute('id','fileSelector');
   file.setAttribute('type','file');
   file.setAttribute('onchange','OC_FILES.upload("'+dir+'")');
   this.uploadForm.appendChild(document.createTextNode('Upload file: '));
   this.uploadForm.appendChild(file);
   parent.appendChild(this.uploadForm);
}

OC_FILES.upload=function(dir){
   OC_FILES.uploadIFrame.setAttribute('onload',"OC_FILES.upload_callback.call(OC_FILES,'"+dir+"')");
   var fileSelector=document.getElementById('fileSelector');
   var max_upload=document.getElementById('max_upload').value;
   if(fileSelector.files && fileSelector.files[0].fileSize){
       var size=fileSelector.files[0].fileSize
       if(size>max_upload){
           return false;
       }
   }
   OC_FILES.uploadForm.submit();
}

OC_FILES.upload_callback=function(dir){
   this.showbrowser(dir);
}

OC_FILES.rename=function(dir,file){
   var item=document.getElementById(file);
   item.oldContent=Array();
   if(item.hasChildNodes()){
      while(item.childNodes.length >=1){
         item.oldContent[item.oldContent.length]=item.firstChild;
         item.removeChild(item.firstChild);
      }
   }
   var form=document.createElement('form');
   form.setAttribute('onsubmit','return OC_FILES.do_rename(\''+dir+'\',\''+file+'\')')
   var input=document.createElement('input');
   input.setAttribute('type','text');
   input.setAttribute('name','newname');
   input.setAttribute('value',file);
   input.setAttribute('id',file+'_newname')
   input.setAttribute('onblur','OC_FILES.rename_cancel(\''+file+'\')');
   form.appendChild(input);
   item.appendChild(form);
   input.focus();
}

OC_FILES.do_rename=function(dir,file){
   var item=document.getElementById(file+'_newname');
   var newname=item.value;
   if(newname==''){
      return false;
   }else if(file==newname){
      OC_FILES.showbrowser(OC_FILES.dir);
      return false;
   }
   xmlloader=new OCXMLLoader();
   xmlloader.setCallBack(OC_FILES.rename_callback);
   xmlloader.load('files/rename.php?dir='+dir+'&file='+file+'&newname='+newname);
   return false;
}

OC_FILES.rename_callback=function(req){
   OC_FILES.showbrowser(OC_FILES.dir);
}

OC_FILES.rename_cancel=function(file){
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

OC_FILES.remove=function(dir,file){
   remove=confirm('remove file \''+file+'\'?');
   if(remove){
      xmlloader=new OCXMLLoader();
      xmlloader.setCallBack(OC_FILES.remove_callback);
      xmlloader.load('files/delete.php?dir='+dir+'&file='+file);
   }
}

OC_FILES.remove_callback=function(req){
   OC_FILES.showbrowser(OC_FILES.dir);
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
    this.extention=file.substr(file.indexOf('.'));
    for(index in OC_FILES.fileActions.all){
        if(OC_FILES.fileActions.all[index].call){
            this.actions[index]=OC_FILES.fileActions.all[index];
        }
    }
    if(OC_FILES.fileActions[this.extention])
    for(index in OC_FILES.fileActions[this.extention]){
        if(OC_FILES.fileActions[this.extention][index].call){
            this.actions[index]=OC_FILES.fileActions[this.extention][index];
        }
    }
}

OC_FILES.file.prototype.showactions=function(){
    OC_FILES.showactions(this.file);
}

OC_FILES.file.prototype.hideactions=function(){
    OC_FILES.showactions(this.file,true);
}

OC_FILES.fileActions=new Object();

OC_FILES.fileActions.all=new Object();

OC_FILES.fileActions.all.remove=function(){
    OC_FILES.remove(this.dir,this.file);
}
OC_FILES.fileActions.all.rename=function(){
    OC_FILES.rename(this.dir,this.file);
}
OC_FILES.fileActions.all.download=function(){
    window.location=WEBROOT+'/files/get_file.php?dir='+this.dir+'&files='+this.file;
}

OC_FILES.showactions=function(file,hide){
    node=document.getElementById(file);
    if(node.actionsshown || hide){
        if(node.actionsdiv){
            node.removeChild(node.actionsdiv);
        }
        node.actionsdiv=null;
        node.actionsshown=false
    }else{
//         OC_FILES.hideallactions();
        node.actionsshown=true
        div=document.createElement('div');
        node.actionsdiv=div;
        div.className='fileactionlist';
        table=document.createElement('table');
        div.appendChild(table);
        tbody=document.createElement('tbody');
        table.appendChild(tbody);
        actions=OC_FILES.files[file].actions;
        for(name in actions){
            if(actions[name].call){
                tr=document.createElement('tr');
                tbody.appendChild(tr);
                td=document.createElement('td');
                tr.appendChild(td);
                a=document.createElement('a');
                td.appendChild(a);
                a.appendChild(document.createTextNode(name));
                td.setAttribute('onclick','OC_FILES.files[\''+file+'\'].actions[\''+name+'\'].call(OC_FILES.files[\''+file+'\'])');
            }
        }
        node.appendChild(div);
        OC_FILES.hideallenabled=false;
        setTimeout('OC_FILES.hideallenabled=true',50);
    }
}

OC_FILES.hideallactions=function(){
    if(OC_FILES.hideallenabled){
        for(name in OC_FILES.files){
            if(OC_FILES.files[name].hideactions){
                OC_FILES.files[name].hideactions.call(OC_FILES.files[name]);
            }
        }
    }
}

OC_FILES.hideallenabled=true; //used to prevent browsers from hiding actionslists right after they are displayed;

sizeFormat=function(size){
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