# RequestCore

RequestCore is a lightweight cURL-based HTTP request/response class that leverages MultiCurl for parallel requests.

### PEAR HTTP_Request?

RequestCore was written as a replacement for [PEAR HTTP_Request](http://pear.php.net/http_request/). While PEAR HTTP_Request is full-featured and heavy, RequestCore features only the essentials and is very lightweight. It also leverages the batch request support in cURL's `curl_multi_exec()` to enable multi-threaded requests that fire in parallel.

### Reference and Download

You can find the class reference at <http://skyzyx.github.com/requestcore/>. You can get the code from <http://github.com/skyzyx/requestcore>.

### License and Copyright

This code is Copyright (c) 2008-2010, Ryan Parman. However, I'm licensing this code for others to use under the [Simplified BSD license](http://www.opensource.org/licenses/bsd-license.php).
