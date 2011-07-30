<form class="searchbox" action="<?php echo $_['searchurl']?>" method="post">
	<input id='searchbox' type="search" name="query" value="<?php if(isset($_POST['query'])){echo $_POST['query'];};?>" class="prettybutton" />
</form>
