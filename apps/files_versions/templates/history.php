<?php
	if(isset($_['message'])){


	        if(isset($_['path'])) echo('<strong>File: '.$_['path']).'</strong><br>';
	        echo('<strong>'.$_['message']).'</strong><br>';

	}else{

	        echo('<strong>Versions of '.$_['path']).'</strong><br>';
	        echo('<p><em>You can click on the revert button to revert to the specific verson.</em></p><br />');
	        foreach ($_['versions'] as $v){
			echo(' '.OC_Util::formatDate($v).' <a href="history.php?path='.urlencode($_['path']).'&revert='.$v.'" class="button">revert</a><br /><br />');
		}

	}

?>
