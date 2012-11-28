<?php
define('_DEBUG_LOG',false);//true);//false);//true);
define('_DEBUG_ELAPSED',false);//true);
define('_DEBUG_PROXY_HOST',false);//"localhost");//false);//'127.0.0.1');
define('_DEBUG_PROXY_PORT',8888);
define('_FIXME_SSL_WORKAROUND',1);

function _ws_http($method,$url,$query,$charset,$cookies,$headers)
{
    if(_DEBUG_ELAPSED)$start_time=microtime(true);

    $ch=curl_init();

    if(!$query)$query=array();
    if(!$charset)$charset="utf-8";
    if(!$headers)$headers=array();

    //기본 헤더
    $headers[]="Accept-Charset: {$charset}";
    //$headers[]="Accept-Encoding: gzip,deflate";

    if($cookies){
	//curl_setopt($ch,CURLOPT_COOKIEFILE,"/dev/null");
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
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_HEADER,1);
    curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch,CURLOPT_ENCODING,"");
    //curl_setopt($ch,CURLOPT_COOKIESESSION,1);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
    curl_setopt($ch,CURLOPT_MAXREDIRS,10);

    if(strcasecmp("post",$method)==0){
	curl_setopt($ch,CURLOPT_POST,1);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$query);
    }else{
	$queryarray=array();
	foreach($query as $k => $v){
	    array_push($queryarray,$k."=".$v);
	}
	$querystring=implode("&",$queryarray);
	if(strlen($querystring)>0){
	    $url.="?".$querystring;
	}
    }

    if(_FIXME_SSL_WORKAROUND){
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0); //level 1
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
    }

    if(_DEBUG_PROXY_HOST){
	error_log("NOTICE: proxy via "._DEBUG_PROXY_HOST.":"._DEBUG_PROXY_PORT."");
	curl_setopt($ch,CURLOPT_PROXY,_DEBUG_PROXY_HOST);
	curl_setopt($ch,CURLOPT_PROXYPORT,_DEBUG_PROXY_PORT);
    }

    curl_setopt($ch,CURLOPT_URL,$url);

    $r=curl_exec($ch);
    if(!$r) {
	error_log("curl_exec({$url}) failed: ".curl_error($ch));
	return false;
    }

    if(_DEBUG_LOG){
	$info=curl_getinfo($ch);
	foreach(explode("\r\n",$info['request_header']) as $l){
	    $l=trim($l);
	    if(strlen($l)>0){
		error_log("===HTTP >>>>===: ".$l);
	    }
	}
    }

    $pos=strpos($r,"\r\n\r\n");
    if(false==$pos){
	error_log("***HTTP***: cannot parse server response: ".$r."\r\n");
	return false;
    }

    $hdr=substr($r,0,$pos);
    $contents=substr($r,$pos+4);

    $newheaders=array();
    $newcookies=array();

    $hh=explode("\r\n",$hdr);

    $http_status=trim(array_shift($hh));// http status line
    if(_DEBUG_LOG)error_log("===HTTP <<<<===: {$http_status}");

    while($l=trim(array_shift($hh))){
	if(strlen($l)>0){
	    if(_DEBUG_LOG)error_log("===HTTP <<<<===: ".$l);
	    list($k,$v)=explode(": ",$l,2);
	    $newheaders[$k]=$v;
	    if(strcasecmp($k,"set-cookie")==0){
		if(preg_match('/^([^=]*)=([^;]*);/',$v,$m)){
		    $newcookies[$m[1]]=$m[2];
		}
	    }
	}
    }

    curl_close($ch);

    if(_DEBUG_ELAPSED){
	$elapsed=microtime(true)-$start_time;
	error_log(sprintf("===HTTP TIME===: %01.2f %s",$elapsed,$url));
    }

    return array(
	    explode(" ",$http_status,3),
	    $newheaders,
	    $charset=="utf-8"?$contents:iconv($charset,"utf-8",$contents),
	    $newcookies);

}

function ws_http_post($url,$query=false,$charset=false,$cookies=false,$headers=false)
{
    return _ws_http("post",$url,$query,$charset,$cookies,$headers);
}

function ws_http_get($url,$query=false,$charset=false,$cookies=false,$headers=false)
{
    return _ws_http("get",$url,$query,$charset,$cookies,$headers);
}

