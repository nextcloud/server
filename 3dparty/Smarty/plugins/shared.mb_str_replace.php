<?php

if(!function_exists('smarty_mb_str_replace')) {
  function smarty_mb_str_replace($search, $replace, $subject, &$count=0) {
      if (!is_array($search) && is_array($replace)) {
          return false;
      }
      if (is_array($subject)) {
          // call mb_replace for each single string in $subject
          foreach ($subject as &$string) {
              $string = &smarty_mb_str_replace($search, $replace, $string, $c);
              $count += $c;
          }
      } elseif (is_array($search)) {
          if (!is_array($replace)) {
              foreach ($search as &$string) {
                  $subject = smarty_mb_str_replace($string, $replace, $subject, $c);
                  $count += $c;
              }
          } else {
              $n = max(count($search), count($replace));
              while ($n--) {
                  $subject = smarty_mb_str_replace(current($search), current($replace), $subject, $c);
                  $count += $c;
                  next($search);
                  next($replace);
              }
          }
      } else {
          $parts = mb_split(preg_quote($search), $subject);
          $count = count($parts)-1;
          $subject = implode($replace, $parts);
      }
      return $subject;
  }
}

?>