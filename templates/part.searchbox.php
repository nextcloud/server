<form class='searchbox' action='<?php echo $_['searchurl']?>' method='post'>
	<input name='query' value='<?php if(isset($_POST['query'])){echo $_POST['query'];};?>'/>
	<input type='submit' value='<?php echo $l->t( 'Search' ); ?>' class='prettybutton'/>
</form>