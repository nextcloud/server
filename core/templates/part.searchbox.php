<form class="searchbox" action="<?php echo $_['searchurl']?>" method="post">
	<input type="text" name="query" value="<?php if(isset($_POST['query'])){echo $_POST['query'];};?>" class="prettybutton" />
	<input type="submit" value="<?php echo $l->t( 'Search' ); ?>" class="prettybutton" />
</form>
