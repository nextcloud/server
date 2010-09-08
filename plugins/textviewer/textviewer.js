OC_TextViewer=new Object();

OC_TextViewer.loader=new OCXMLLoader();
OC_TextViewer.showText=function(dir,file){
	var path=WEBROOT+'/files/open_file.php?dir='+encodeURIComponent(dir)+'&file='+encodeURIComponent(file);
	var div=document.createElement('div');
	div.setAttribute('id','textframe');
	div.setAttribute('class','center');
	div.addEvent('onclick',OC_TextViewer.hideText)
	OC_TextViewer.textFrame=document.createElement('div');
	div.appendChild(OC_TextViewer.textFrame);
	body=document.getElementsByTagName('body').item(0);
	body.appendChild(div);
	OC_TextViewer.loader.setCallBack(OC_TextViewer.showTexCallback);
	OC_TextViewer.loader.load(path);
}

OC_TextViewer.showTexCallback=function(req){
	var text=req.responseText;
	OC_TextViewer.textFrame.innerHTML=OC_TextViewer.prepareText(text);
}

OC_TextViewer.hideText=function(){
	var div=document.getElementById('textframe');
	div.parentNode.removeChild(div);
} 

OC_TextViewer.prepareText=function(text){
	text=text.replace(/\n/g,"<br/>\n");
	text=text.replace(/ /g,"&nbsp;");
	text=text.replace(/\t/g,"&nbsp;&nbsp;&nbsp;&nbsp;");
	return text;
}

if(!OC_FILES.fileActions.text){
	OC_FILES.fileActions.text=new Object()
}
OC_FILES.fileActions.text.show=function(){
	OC_TextViewer.showText(this.dir,this.file);
}

OC_FILES.fileActions.text['default']=OC_FILES.fileActions.text.show;