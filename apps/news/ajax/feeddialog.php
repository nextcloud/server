<?php

include("populateroot.php");

$output = new OCP\Template("news", "part.addfeed");
$output -> assign('allfeeds', $allfeeds);
$output -> printpage();