ws_http
=======

* http protocol wrapper for php
* ws_run ( a simple php web-sapi emulation from cli ) feature added
* in addition to prebuilt file_get_contents() or curl_XXX() function features,
  + per site cookie management
    - cookie-s read from in $_SESSION[] variable before ws_browser_get()/ws_browser_post()
    - new set-cookie-s written into $_SESSION[] variable after ws_browser_get()/ws_browser_post()
* can be used as php cli script to fetch url
  + Usage: php ws_http.php https://raw.github.com/rhee/ws_http/master/README.md
* can be sued as php cli script to test other php script require_once'ing ws_http.php
  + Usage: php ws_http.php other_php_script.php key1=value1 key2=value2 ...
* in cli mode, received set-cookie's are stored in session.json file in current directory, and then re-read back when next time you call ws_http.php cli at current directory.
