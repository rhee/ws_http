<?php
/*
Example:

$ch=curl_init();
$cookies=array();
echo ws_fetch_url($ch,"https://www.mogun.ms.kr:1456/member/login.do",array(),$cookies);
error_log(print_r($cookies,1));
*/

$_debug=1;
//$_debug_proxy_host="127.0.0.1";
//$_debug_proxy_port=8888;
$_fixme_ssl_workaround=1;

function ws_fetch_url($ch,$url,$options,&$cookies)
{
	global $_debug;
	global $_debug_proxy_host;
	global $_debug_proxy_port;
	global $_fixme_ssl_workaround;

	if(!isset($ch)||!$ch)$ch=curl_init();

	//기본 헤더
	$h[]="Accept-Charset: utf-8";
	//$h[]="Accept-Encoding: gzip,deflate";

	if(isset($cookies)){
		$cookiearray=array();
		foreach($cookies as $k => $v){
			array_push($cookiearray,$k."=".$v);
		}
		$cookiestring=implode("; ",$cookiearray);
		if(strlen($cookiestring)>0){
			curl_setopt($ch,CURLOPT_COOKIE,$cookiestring);
		}
	}

	curl_setopt($ch,CURLINFO_HEADER_OUT,1);
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_HEADER,1);
	curl_setopt($ch,CURLOPT_HTTPHEADER,$h);
	curl_setopt($ch,CURLOPT_ENCODING,"");
	curl_setopt($ch,CURLOPT_COOKIESESSION,1);

	if(isset($options['post'])&&$options['post']){
		$postfields=$options['postfields'];
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postfields);
	}

	if(isset($_fixme_ssl_workaround)){
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0); //level 1
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
	}

	if(isset($_debug_proxy_host)){
		if($_debug)error_log("NOTICE: proxy via {$_debug_proxy_host}:{$_debug_proxy_port}");
		curl_setopt($ch,CURLOPT_PROXY,$_debug_proxy_host);
		curl_setopt($ch,CURLOPT_PROXYPORT,$_debug_proxy_port);
	}

	if(isset($options['header'])){
		$h=array_merge($h,$options['header']);
	}

	$r=curl_exec($ch); //$r||die("curl_exec() failed ".curl_error($ch)."\r\n");
	if(!$r) {
		error_log("curl_exec({$url}) failed: ".curl_error($ch));
		return false;
	}

	$pos=strpos($r,"\r\n\r\n");
	if(false==$pos){
		error_log("ws_fetch_url(): cannot parse server response: ".$r."\r\n");
		return false;
	}

	$headers=substr($r,0,$pos);
	$contents=substr($r,$pos+4);

	$cookies=array();

	$hh=explode("\r\n",$headers);

	while($l=array_shift($hh)){
		if(preg_match('/^Set-Cookie: ([^=]*)=([^;]*);/',$l,$m)){
			$cookies[$m[1]]=$m[2];
		}
	}

	if($_debug){
		$info=curl_getinfo($ch);
		//error_log("curl info: ".print_r($info,1));
		error_log("curl request:\r\n".print_r($info['request_header'],1));
		error_log("curl response:\r\n".print_r($headers,1));
	}

	return $contents;
}

