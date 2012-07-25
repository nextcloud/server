<?php

include("populateroot.php");

$output = new OCP\Template("news", "part.addfolder");
$output -> assign('allfeeds', $allfeeds);
$output -> printpage();