<?php
require "ws_fetch_url.php";

$userid="<userid>";
$userpasswd="<password>";
$returnUrl="/index/index.do";

$ch=curl_init();
$cookies=array();

$url2="https://www.mogun.ms.kr:1456/member/login.do";
$result2=ws_fetch_url(
	$ch,
	$url2,
	array(
		'post'=>1,
		'postfields'=>array(
				'userid'=>$userid,
				'userpasswd'=>$userpasswd,
				'returnUrl'=>$returnUrl)),
	$cookies);

error_log("step1 finished, cookies=".print_r($cookies,1));
echo $result2;

curl_close($ch);
$ch=curl_init();

$url3="https://www.mogun.ms.kr:1456/schoolContent/schoolGreeting.do";

$result3=ws_fetch_url(
	$ch,
	$url3,
	array(),
	$cookies);

error_log("step2 finished, cookies=".print_r($cookies,1));
echo $result3;

curl_close($ch);
