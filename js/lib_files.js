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
          for(index in fileElements){
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
    
    //remove current content;
    var contentNode=document.getElementById('content');
    if(contentNode.hasChildNodes()){
       while(contentNode.childNodes.length >=1){
          contentNode.removeChild(contentNode.firstChild);
       }
    }
    
    // breadcrumb
    if(dirs.length>0) {
       var breadcrumb=document.createElement('div');
       breadcrumb.className='center';
       var table=document.createElement('table');
       breadcrumb.appendChild(table);
       table.setAttribute('cellpadding',2);
       table.setAttribute('cellspacing',0);
       var tbody=document.createElement('tbody');//some IE versions need this
       table.appendChild(tbody);
       tr=document.createElement('tr');
       tbody.appendChild(tr);
       td=document.createElement('td');
       tr.appendChild(td);
       td.className='nametext';
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
             td=document.createElement('td');
             tr.appendChild(td);
             td.className='nametext';
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
      contentNode.appendChild(breadcrumb);
    }

    // files and directories
    var files=document.createElement('div');
    OC_FILES.browser=files;
    files.className='center';
    var table=document.createElement('table');
    files.appendChild(table);
    table.setAttribute('cellpadding',6);
    table.setAttribute('cellspacing',0);
    table.className='browser';
    var tbody=document.createElement('tbody');//some IE versions need this
    table.appendChild(tbody);
    var filesfound=false;
    var sizeTd=null;
    if(content){
       for(index in content){
          file=content[index];
          if(file.name){
             tr=document.createElement('tr');
             tbody.appendChild(tr);
             tr.className='browserline';
             tr.appendChild(OC_FILES.showicon(file['type']));
             td=document.createElement('td');
             tr.appendChild(td);
             td.className='nametext';
             td.setAttribute('name',file['name']);
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
                sizeTd.appendChild(document.createTextNode(file['size']+' bytes'));
             }
             a=document.createElement('a');
             img=document.createElement('img');
             td.appendChild(img);
             img.className='rename';
             img.alt='rename'
             img.title='rename';
             img.src=WEBROOT+'/img/icons/rename.png';
             img.style.height='16px'
             img.style.width='16px'
             img.setAttribute('onclick','OC_FILES.rename(\''+dir+'\',\''+file['name']+'\')')
             td=document.createElement('td');
             tr.appendChild(td);
             td.className='sizetext';
             td.appendChild(document.createTextNode(file['date']));
             if(file['type']!='dir'){
                td=document.createElement('td');
                tr.appendChild(td);
                img=document.createElement('img');
                td.appendChild(img);
                img.className='delete';
                img.alt='delete'
                img.title='delete';
                img.src=WEBROOT+'/img/icons/delete.png';
                img.style.height='16px'
                img.style.width='16px'
                img.setAttribute('onclick','OC_FILES.remove(\''+dir+'\',\''+file['name']+'\')')
             }
          }
       }
    }
    tr=document.createElement('tr');
    tbody.appendChild(tr);
    td=document.createElement('td');
    tr.appendChild(td);
    td.className='upload';
    td.setAttribute('colspan','5');
    this.showuploader(dir,td,content['max_upload']);
    contentNode.appendChild(files);
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
   this.uploadForm.appendChild(input);
   var file=document.createElement('input');
   file.name='file';
   file.setAttribute('type','file');
   file.setAttribute('onchange','OC_FILES.upload("'+dir+'")');
   this.uploadForm.appendChild(document.createTextNode('Upload file: '));
   this.uploadForm.appendChild(file);
   parent.appendChild(this.uploadForm);
}

OC_FILES.upload=function(dir){
   OC_FILES.uploadIFrame.setAttribute('onload',"OC_FILES.upload_callback.call(OC_FILES,'"+dir+"')");
   OC_FILES.uploadForm.submit();
}

OC_FILES.upload_callback=function(dir){
   this.showbrowser(dir);
}

OC_FILES.rename=function(dir,file){
   var item=document.getElementsByName(file).item(0);
   item.oldContent=new Array();
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