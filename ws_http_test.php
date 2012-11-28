<?php
require "ws_http.php";

$userid="<userid>";
$userpasswd="<password>";
$returnUrl="/index/index.do";

$url2="https://www.mogun.ms.kr:1456/member/login.do";
list($status,$headers,$contents,$newcookies)=
	ws_http_post(
		$url2,
		array(	'userid'=>$userid,
			'userpasswd'=>$userpasswd,
			'returnUrl'=>$returnUrl	));

error_log("step1 finished, cookies=".implode(" ",explode("\n",print_r($newcookies,1))));
file_put_contents("page1.html", $contents);

$url3="https://www.mogun.ms.kr:1456/schoolContent/schoolGreeting.do";

$cookies=array_merge(array(),$newcookies);

list($status,$headers,$contents,$newcookies)=
	ws_http_get($url3,false,false,$cookies);

error_log("step2 finished, cookies=".implode(" ",explode("\n",print_r($newcookies,1))));
file_put_contents("page2.html", $contents);

