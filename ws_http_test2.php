<?php
require_once "../http/ws_http.php";

$_debug=true;

$charset="euc-kr";
$userid="<userid>";
$password="<password>";

/*

// user-agent example:
$optheaders=array(
	"Referer: http://hh.es.kr/index.jsp",
	"Origin: http://hh.es.kr",
	"User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11",
);

*/

/*

// login query example:
$login_query=array(
	"x"=>"0",
	"y"=>"0",
	"ssl"=>"on",
	"redirectPath"=>"http://hh.es.kr/index.jsp",
	"className"=>"newclass.workers.doum.DoumUserWorker",
	"actionMode"=>"login",
	"i_id"=>$userid,
	"i_passwd"=>$password,
	"i_sid"=>"NTYzNTA4RkNDNzg1M0YxMzlGREU3NDM2QUFCRTc2RTk=",
	"i_server_url"=>"http://hh.es.kr",
	"SCODE"=>"localhost",
	"cmd"=>"",
	"frame"=>"",
	"memo"=>"",
	"mnu"=>"",
	"loginimg"=>"login_title.gif",
	"pw_img"=>"pw.gif",
	"focus"=>"true",
	"id_img"=>"id.gif",
	"i_errorPage"=>"http://hh.es.kr/index.jsp?&cmd=&frame=&memo=0&mnu=&loginimg=login_title.gif&pw_img=pw.gif&SCODE=localhost&focus=true&id_img=id.gif",
);
*/

# greeting page (http)
$url="http://www.hh.es.kr/index.jsp";
list($status,$headers,$contents,$newcookies)=
  ws_http_get($url,false,$charset,false,false);
foreach($newcookies as $c)error_log("set-cookies: ".$c);

$sessioncookies=array_merge(array(),$newcookies);

$login_query['i_sid']=base64_encode($sessioncookies['JSESSIONID']);
$login_query['i_id']=$userid;
$login_query['i_passwd']=$password;
$login_query['ssl']="on";
$login_query['SCODE']="localhost";
$login_query['actionMode']="login";
$login_query['className']="newclass.workers.doum.DoumUserWorker";

# login page (https)
$url="https://www.hh.es.kr/servlet/Controller";
list($status,$headers,$contents)=
  ws_http_post($url,$login_query,$charset,$sessioncookies);

//# next page
//$url="http://hh.es.kr/index.jsp?SCODE=localhost&mnu=M001001001";
//list($status,$headers,$contents)=
//  ws_http_get($url,false,$charset,$sessioncookies,false);
//file_put_contents("page2.html",$contents);

# next page ( password protected )
$url="http://hh.es.kr/index.jsp?cmd=&frame=&mnu=M001009021&ssl=on&sub=y&y=0&loginimg=login_title.gif&SCODE=localhost&pw_img=pw.gif&x=0&id_img=id.gif";
list($status,$headers,$contents)=
  ws_http_get($url,false,$charset,$sessioncookies,false);
file_put_contents("page3.html",$contents);
