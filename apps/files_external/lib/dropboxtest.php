<?php

require_once 'dropbox.php';
// $oauth = new Dropbox_OAuth_Curl('526ar3qlrtzmv65', '3bbn0wo5lzgpjty');
$dropbox = new OC_Filestorage_Dropbox(array('app_key' => '526ar3qlrtzmv65', 'app_secret' => '3bbn0wo5lzgpjty', 'token' => 'a3ben02jb1y538a',    'token_secret' => 'x60h3fsky21r1b0'));
$dropbox->rename('/652072main_2012-2897_full (1).jpg', '/test.jpg');
// $dropbox->test();
// print_r($dropbox->mkdir('Again'));

// GET&https%3A%2F%2Fapi.dropbox.com%2F1%2Fmetadata%2Fdropbox%2FownCloud&list%3D0%26

// uzpi8oo2rbax1po
// th9uoso3xxny3ca
//Step 3: Acquiring access tokens Array ( [token] => 37my637p88ng967 [token_secret] => t49fmgp3omucnnr )
//The user is authenticated You should really save the oauth tokens somewhere, so the first steps will no longer be needed Array ( [token] => 37my637p88ng967 [token_secret] => t49fmgp3omucnnr )

// For convenience, definitely not required
// header('Content-Type: text/plain');

// // We need to start a session
// session_start();

// There are multiple steps in this workflow, we keep a 'state number' here
// if (isset($_SESSION['state'])) {
//     $state = 2;
// } else {
//     $state = 1;
// }
// 
// switch($state) {
// 
//     /* In this phase we grab the initial request tokens
//        and redirect the user to the 'authorize' page hosted
//        on dropbox */
//     case 1 :
//         echo "Step 1: Acquire request tokens\n";
//         $tokens = $oauth->getRequestToken();
//         print_r($tokens);
// 
//         // Note that if you want the user to automatically redirect back, you can
//         // add the 'callback' argument to getAuthorizeUrl.
//         echo "Step 2: You must now redirect the user to:\n";
//         echo $oauth->getAuthorizeUrl() . "\n";
//         $_SESSION['state'] = 2;
//         $_SESSION['oauth_tokens'] = $tokens;
//         die();
// 
//     /* In this phase, the user just came back from authorizing
//        and we're going to fetch the real access tokens */
//     case 2 :
//         echo "Step 3: Acquiring access tokens\n";
//         $oauth->setToken($_SESSION['oauth_tokens']);
//         $tokens = $oauth->getAccessToken();
//         print_r($tokens);
//         $_SESSION['state'] = 3;
//         $_SESSION['oauth_tokens'] = $tokens;
//         // There is no break here, intentional
// 
//     /* This part gets called if the authentication process
//        already succeeded. We can use our stored tokens and the api
//        should work. Store these tokens somewhere, like a database */
//     case 3 :
//         echo "The user is authenticated\n";
//         echo "You should really save the oauth tokens somewhere, so the first steps will no longer be needed\n";
//         print_r($_SESSION['oauth_tokens']);
//         $oauth->setToken($_SESSION['oauth_tokens']);
//         break;
// 
// }

?>