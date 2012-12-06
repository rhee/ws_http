<?php
define('_DEBUG_LOG',false);//false);//true);
define('_DEBUG_ELAPSED',true);//false);//true);
define('_DEBUG_PROXY_HOST',false);//'127.0.0.1');
define('_DEBUG_PROXY_PORT',8888);
define('_FIXME_SSL_WORKAROUND',1);
define('_FAKE_USER_AGENT','User-Agent: Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11');

function _cookieencode($str)
{
/*
XXX TBD FIXME
There is some confusion over encoding of a cookie value. The commonly held belief is
that cookie values must be URL-encoded, but this is a fallacy even though it is the de
facto implementation. The original specification indicates that only three types of
characters must be encoded: semicolon, comma, and white space. The specification
indicates that URL encoding may be used but stops short of requiring it. The RFC makes
no mention of encoding whatsoever. Still, almost all implementations perform some sort
of URL encoding on cookie values. In the case of name=value formats, the name and value
are typically encoded separately while the equals sign is left as is.
*/
    //return rawurlencode($str);
    return $str;
}

function _ws_http($method,$url,$query,$charset,$cookies,$headers)
{
    if(_DEBUG_ELAPSED)$start_time=microtime(true);

    $ch=curl_init();

    if(!$query)$query=array();
    if(!$charset)$charset="utf-8";
    if(!$headers)$headers=array();

    //기본 헤더
    $h=array();

    //추가 헤더
    $h=array_merge($h,$headers);

    if($cookies){
	$cookiearray=array();
	foreach($cookies as $k => $v)array_push($cookiearray,_cookieencode($k)."="._cookieencode($v));
	$cookiestring=implode("; ",$cookiearray);
	if(strlen($cookiestring)>0)curl_setopt($ch,CURLOPT_COOKIE,$cookiestring);
    }

    curl_setopt($ch,CURLINFO_HEADER_OUT,1);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_HEADER,1);
    curl_setopt($ch,CURLOPT_HTTPHEADER,$h);
    curl_setopt($ch,CURLOPT_ENCODING,"");
    //curl_setopt($ch,CURLOPT_COOKIESESSION,1);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
    curl_setopt($ch,CURLOPT_MAXREDIRS,10);

    $querystring=http_build_query($query);

    if(strcasecmp("post",$method)==0){
	curl_setopt($ch,CURLOPT_POST,1);
	//curl_setopt($ch,CURLOPT_POSTFIELDS,$query);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$querystring);
    }else{
	if(strlen($querystring)>0){
	    $url.="?".$querystring;
	}
    }

    if(_FIXME_SSL_WORKAROUND){
        curl_setopt($ch,CURLOPT_VERBOSE,true);
        curl_setopt($ch,CURLOPT_CERTINFO,true);
        //curl_setopt($ch,CURLOPT_STDERR,"curl_ssl_info.log");
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
	error_log("");
	$info=curl_getinfo($ch);
	foreach(explode("\r\n",$info['request_header']) as $l){
	    $l=trim($l);
	    if(strlen($l)>0){
		error_log("===http request===: ".$l);
	    }
	}

        if(0==strcasecmp("POST",$method)){
	    error_log("");
	    error_log("===http form data===: ".$querystring);
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
    if(_DEBUG_LOG){
	error_log("");
	error_log("===http response===: {$http_status}");
    }

    while($l=trim(array_shift($hh))){
	if(strlen($l)>0){
	    if(_DEBUG_LOG)error_log("===http response===: ".$l);
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
        $status_triple=explode(" ",$http_status,3);
	$status_code=$status_triple[1];
	$elapsed=microtime(true)-$start_time;
        error_log("");
	error_log(sprintf("===http time===: %01.2f %s %s %s",$elapsed,$status_code,strtoupper($method),$url));
    }

    return array(
	    explode(" ",$http_status,3),
	    $newheaders,
	    $charset=="utf-8"?$contents:iconv($charset,"utf-8",$contents),
	    $newcookies);

}

function ws_http_post($url,$query=false,$charset=false,$cookies=false,$headers=false)
{
    error_log("OBSOLETE: ws_http_post deprecated, use ws_browser_post()");
    return _ws_http("post",$url,$query,$charset,$cookies,$headers);
}

function ws_http_get($url,$query=false,$charset=false,$cookies=false,$headers=false)
{
    error_log("OBSOLETE: ws_http_get deprecated, use ws_browser_get()");
    return _ws_http("get",$url,$query,$charset,$cookies,$headers);
}

////////////////////////////////////
/// utility
////////////////////////////////////

function ws_http_build_url($urlinfo)
{
    // urlinfo keys: scheme host port user pass path query fragment
    return $urlinfo['scheme'].'://'.
           $urlinfo['host'].
           (isset($urlinfo['port'])?':'.$urlinfo['port']:'').
           $urlinfo['path'].
           (isset($urlinfo['query'])?'?'.$urlinfo['query']:'').
           (isset($urlinfo['fragment'])?'#'.$urlinfo['fragment']:'');
}

function ws_http_join_url($url,$path)
{
    if(preg_match('/^http(s)?:/',$path)){
        return $path;
    }
    $urlinfo=parse_url($url);
    unset($urlinfo['path']);
    unset($urlinfo['query']);
    unset($urlinfo['fragment']);
    $urlinfo['path']=$path;
    return ws_http_build_url($urlinfo);
}

function ws_http_merge_cookies($cookie1,$cookie2)
{
    if(!$cookie1)return $cookie2;
    return $cookie2?array_merge($cookie1,$cookie2):$cookie1;
}

////////////////////////////////
/// browser
////////////////////////////////

function ws_browser_init($headers=false,$cookies=false)
{
    if(false===$headers){
        $headers=array(_FAKE_USER_AGENT,);
    }
    if(false===$cookies){
        if(isset($_SESSION['ws_browser_cookies'])){
            $cookies=$_SESSION['ws_browser_cookies'];
        }else{
            $cookies=array();
        }
    }
    $browser=array(
	"cookies"=>$cookies,
        "headers"=>$headers,
        "charset"=>"utf-8",
    );
    return $browser;
}

function ws_browser_close(&$browser)
{
}

function ws_browser_set_charset(&$browser,$charset)
{
    $browser["charset"]=$charset;
}

function ws_browser_add_headers(&$browser,$newheaders)
{
    $browser["headers"]=array_merge($browser["headers"],$newheaders);
}

function ws_browser_charset(&$browser)
{
    return $browser["charset"];
}

function ws_browser_host(&$browser)
{
    return $browser["host"];
}

function ws_browser_status(&$browser)
{
    return $browser["status"][1];
}

function ws_browser_response(&$browser)
{
    return $browser["response"];
}

function ws_browser_setcookies(&$browser)
{
    return $browser["setcookies"];
}

function ws_browser_cookies(&$browser)
{
    return $browser["cookies"];
}

function ws_browser_host_cookie(&$browser,$key=false,$host=false)
{
    if(!$host)$host=ws_browser_host($browser);
    return $key?$browser["cookies"][$host][$key]:$browser["cookies"][$host];
}

function ws_browser_load_cookies(&$browser,$cookies)
{
    $browser["cookies"]=$cookies;
}

function ws_browser_get(&$browser,$url,$query=false,$headers=false,$extracookies=false)
{
    $host=parse_url($url,PHP_URL_HOST);
    $charset=$browser["charset"];
    $cookies=false;
    if(isset($browser["cookies"],$browser["cookies"][$host]))$cookies=$browser["cookies"][$host];
    if($extracookies)$cookies=ws_http_merge_cookies($cookies,$extracookies);
    $tmpheaders=array_merge($browser["headers"],$headers?$headers:array());
    list($status,$response,$contents,$setcookies)=
        _ws_http("GET",$url,$query,$charset,$cookies,$tmpheaders);
    $browser["host"]=$host;
    $browser["status"]=$status[1];
    $browser["response"]=$response;
    $browser["setcookies"]=$setcookies;
    $cookies=ws_http_merge_cookies($cookies,$setcookies);
    $browser["cookies"][$host]=$cookies;
    $_SESSION['ws_browser_cookies']=$browser["cookies"];
    return $contents;
}

function ws_browser_post(&$browser,$url,$query=false,$headers=false,$extracookies=false)
{
    $host=parse_url($url,PHP_URL_HOST);
    $charset=$browser["charset"];
    $cookies=false;
    if(isset($browser["cookies"],$browser["cookies"][$host]))$cookies=$browser["cookies"][$host];
    if($extracookies)$cookies=ws_http_merge_cookies($cookies,$extracookies);
    $tmpheaders=array_merge($browser["headers"],$headers?$headers:array());
    list($status,$response,$contents,$setcookies)=
        _ws_http("POST",$url,$query,$charset,$cookies,$tmpheaders);
    $browser["host"]=$host;
    $browser["status"]=$status[1];
    $browser["response"]=$response;
    $browser["setcookies"]=$setcookies;
    $cookies=ws_http_merge_cookies($cookies,$setcookies);
    $browser["cookies"][$host]=$cookies;
    $_SESSION['ws_browser_cookies']=$browser["cookies"];
    return $contents;
}

