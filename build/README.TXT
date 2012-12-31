How to setup build.xml on your local machine?

1.) Install ant
 e.g. apt-get install ant

2.) Install jslint
 Get the latest distribution from http://code.google.com/p/jslint4java/ and 
 place jslint4java-*.jar in your home under .ant/lib/

3.) Install PHP qa tools
 Run as root:
  pear config-set auto_discover 1
  pear install pear.phpqatools.org/phpqatools pear.netpirates.net/phpDox

 Alternative:
  pear install pear.phpunit.de/phploc
  pear install pear.phpunit.de/phpcpd
  pear install pear.phpmd.org/PHP_PMD
  pear install pear.pdepend.org/PHP_Depend
  pear install pear.php.org/PHP_CodeSniffer
  pear install PHP_CodeSniffer
  pear install pear.phpqatools.org/PHP_CodeBrowser


How to call this ant script?
 ant -f  build/build.xml -Dbasedir=. 


