<script type="text/javascript">
function showForm(id){
	hideAllForms();
	form=document.getElementById('settingsContent_'+id);
	form.setAttribute('class','settingsContent');
}

function showFormInit(){
	var loc=document.location.toString();
	if(loc.indexOf('#')!=-1){
		var id=loc.substring(loc.indexOf('#')+1);
		showForm(id);
	}
}

function hideAllForms(){
	forms=document.getElementById('settingsHolder').childNodes;
	for(var i=0;i<forms.length;i++){
		form=forms.item(i);
		if(form.nodeType==1 && (form.tagName=='div' || form.tagName=='DIV')){
			form.setAttribute('class','settingsContent hidden');
		}
	}
}
</script>
<div id='settingsNav'>
<ul>
<?php
global $WEBROOT;
foreach(OC_CONFIG::$forms as $name=>$url){
	$clean=strtolower(str_replace(' ','_',$name));
	echo("<li><a onclick='showForm(\"$clean\")' href='{$WEBROOT}/settings/#$clean'>$name</a></li>\n");
}
?>
</ul>
</div>
<div id='settingsHolder'>
<div class='settingsContent'>Settings</div>
<?php
foreach(OC_CONFIG::$forms as $name=>$url){
	$clean=strtolower(str_replace(' ','_',$name));
	echo("<div id='settingsContent_$clean' class='settingsContent hidden'>\n");
	oc_include($url);
	echo("</div>\n");
}
?>
</div>
<script type="text/javascript">
OC_onload.add(showFormInit());
</script>