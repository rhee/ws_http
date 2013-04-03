package kr.co.websync.http;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.UnsupportedEncodingException;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLEncoder;
import java.security.SecureRandom;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;
import java.util.HashMap;
import java.util.Map;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLSession;
import javax.net.ssl.X509TrustManager;

import android.content.Context;
import android.os.AsyncTask;
import android.os.Build;
import android.util.Log;

public class SimpleHttp extends AsyncTask<String,Void,Throwable> {

    final static String LOG_TAG="CoupleObserver";//"HttpTest";

    static {
	//allow self-signed cert ( in fact, disables host name check )
	trustEveryone();
	disableConnectionReuseIfNecessary();// android-specific
    }

    protected boolean do_post;
    protected String http_url;	//request url
    protected String http_params_raw;
    
    protected Throwable http_error;
    protected int http_status;
    protected long http_server_time;
    protected String http_response;

    private SimpleHttpCallbackWrapper http_callback;

    public interface SimpleHttpCallback {
	public abstract void onHttpOk(int status,long server_time,String response);
	public abstract void onHttpError(Throwable error,int status,String response);
    }


    private class SimpleHttpCallbackWrapper {

	protected SimpleHttp task;
	protected SimpleHttpCallback callback;

	final void setTask(SimpleHttp task){this.task=task;}
	SimpleHttp getTask(){return task;}

	/* subclass can access response using this methods */
	protected final Throwable getError(){return getTask().http_error; }  
	protected final int getStatus(){return getTask().http_status;}
	protected final long getServerTime(){return getTask().http_server_time;}
	protected final String getResponse(){return getTask().http_response;}

	public SimpleHttpCallbackWrapper(SimpleHttpCallback callback) {
	    this.callback=callback;
	}

	private Map<String,String> getResponseFormURLAsMap() {
	    HashMap<String,String> map=new HashMap<String,String>();
	    if(null!=getResponse()&&0<getResponse().length()){
		for(String kv : getResponse().split("&")){
		    String[] s = kv.split("=");//Log.v(LOG_TAG,"Http.getResponseFormURLAsMap: k="+s[0]+", v="+s[1]);
		    map.put(s[0],s[1]);
		}
	    }
	    return map;
	}

	public void run() {
	    if(null==getError()&&(
		    200==getStatus()
		    )) {
		if(null!=callback)callback.onHttpOk(getStatus(),getServerTime(),getResponse());
	    }else{
		if(null!=callback)callback.onHttpError(getError(),getStatus(),getResponse());
	    }
	}

    }

    public SimpleHttp(String url,String params){//,SimpleHttpCallback callback) {
	super();
	this.http_url=url;
        this.http_params_raw=params;
	this.http_callback=null;//new SimpleHttpCallbackWrapper(callback);
	this.do_post=false;
    }

    public SimpleHttp(String url,Map<String,String>params){//,SimpleHttpCallback callback) {
        this(url,mapToString(params));
    }

    public SimpleHttp(String url,String[]params){//,SimpleHttpCallback callback) {
        this(url,stringsToString(params));
    }

    @Override
    protected Throwable doInBackground(String... requests){

	try {

	    String url=http_url;

	    if(!this.do_post) url+="?"+http_params_raw;

	    HttpURLConnection con=(HttpURLConnection)(new URL(url)).openConnection();

	    try{

		con.setInstanceFollowRedirects(true);

		if(this.do_post){
                    con.setDoOutput(true);
                    con.setChunkedStreamingMode(0);
                    BufferedWriter out=new BufferedWriter(new OutputStreamWriter(con.getOutputStream()));
                    out.write(http_params_raw);
                    //out.newLine();
                    out.close();
                }

                http_status=con.getResponseCode();
                http_server_time=con.getHeaderFieldDate("date",0);

                StringBuffer buf=new StringBuffer();
                BufferedReader in=new BufferedReader(new InputStreamReader(con.getInputStream()));
                String line=null;
                while(null!=(line=in.readLine())){
                    buf.append(line);//.append("\n");
                }
                in.close();

		http_response=buf.toString();

	    }finally{
		con.disconnect();
	    }

	}catch(MalformedURLException e){
	    Log.e(LOG_TAG,"HttpUrlConnection error",e);
	    return e;
	}catch(IOException e){
	    Log.e(LOG_TAG,"HttpUrlConnection error",e);
	    return e;
	}
	return null;

    }

    @Override
    protected void onPostExecute(Throwable error){
	if(null==error){
	    Log.v(LOG_TAG,"Http.execute: status="+http_status+", response=\""+http_response+"\", time="+http_server_time);
	}
	if(null!=http_callback){
	    http_callback.run();
	}
    }

//    public SimpleHttp execute(final SimpleHttpCallback callback) /*throws IOException*/ {
//
//	this.http_error=null;
//	this.http_status=0;	//(re-)initalize
//	this.http_server_time=0;
//	this.http_response=null;
//	
//	this.http_callback=new SimpleHttpCallbackWrapper(callback);
//
//	this.http_callback.setTask(this);
//
//	Log.v(LOG_TAG,"Http.execute: url=\""+http_url+"\", params=\""+http_params_raw+"\"");
//
//	super.execute();
//	
//	return this;
//
//    }

    public SimpleHttp get(final SimpleHttpCallback callback) /*throws IOException*/ {

        this.do_post=false;
	this.http_error=null;
	this.http_status=0;	//(re-)initalize
	this.http_server_time=0;
	this.http_response=null;
	
	this.http_callback=new SimpleHttpCallbackWrapper(callback);

	this.http_callback.setTask(this);

	Log.v(LOG_TAG,"Http.execute: url=\""+http_url+"\", params=\""+http_params_raw+"\"");

	super.execute();
	
	return this;

    }

    public SimpleHttp post(final SimpleHttpCallback callback) /*throws IOException*/ {

        this.do_post=true;
	this.http_error=null;
	this.http_status=0;	//(re-)initalize
	this.http_server_time=0;
	this.http_response=null;
	
	this.http_callback=new SimpleHttpCallbackWrapper(callback);

	this.http_callback.setTask(this);

	Log.v(LOG_TAG,"Http.execute: url=\""+http_url+"\", params=\""+http_params_raw+"\"");

	super.execute();
	
	return this;

    }

    //////////////////////////////////////////////////////
    //////////////////////////////////////////////////////
    //////////////////////////////////////////////////////

    private static String mapToString(Map<String,String> map){
	try {
            StringBuffer postbuf=new StringBuffer();
            for(Map.Entry<String,String> entry : map.entrySet()){
                postbuf
                .append("&")
                .append(URLEncoder.encode(entry.getKey(),"UTF-8"))
                .append("=")
                .append(URLEncoder.encode(entry.getValue(),"UTF-8"));
            }
            return postbuf.substring(1);//skip first "&"
	}catch(UnsupportedEncodingException e){
	    Log.e(LOG_TAG,"SimpleHttp",e);
	    return "";
        }
    }

    private static String stringsToString(String[] params){
	try {
            StringBuffer postbuf=new StringBuffer();
            for(int i=0;i<params.length/2;i++){
                postbuf
                .append("&")
                .append(URLEncoder.encode(params[i*2],"UTF-8"))
                .append("=")
                .append(URLEncoder.encode(params[i*2+1],"UTF-8"));
            }
            return postbuf.substring(1);//skip first "&"
	}catch(UnsupportedEncodingException e){
	    Log.e(LOG_TAG,"SimpleHttp",e);
	    return "";
        }
    }

    //////////////////////////////////////////////////////
    //////////////////////////////////////////////////////
    //////////////////////////////////////////////////////

    //android-specific: allow android specific http cache feature
    public static void enableHttpResponseCache(Context context) {
	try {
	    long httpCacheSize = 10 * 1024 * 1024; // 10 MiB
	    File httpCacheDir = new File(context.getCacheDir(), "http");
	    Class.forName("android.net.http.HttpResponseCache")
	    .getMethod("install", File.class, long.class)
	    .invoke(null, httpCacheDir, httpCacheSize);
	} catch (Exception httpResponseCacheNotAvailable) {
	    /* ignore if not implemented */
	}
    }

    //////////////////////////////////////////////////////
    //////////////////////////////////////////////////////
    //////////////////////////////////////////////////////

    //android-specific: workaround for pre-froyo socket pool leak
    private static void disableConnectionReuseIfNecessary() {
	// HTTP connection reuse which was buggy pre-froyo
	if (Integer.parseInt(Build.VERSION.SDK) <= Build.VERSION_CODES.ECLAIR_MR1){// <Build.VERSION_CODES.FROYO) {
	System.setProperty("http.keepAlive", "false");
	}
    }

    //java-generic: allow self-signed cert ( in fact, disables host name check )
    private static void trustEveryone() {
	try {
	    HttpsURLConnection.setDefaultHostnameVerifier(new HostnameVerifier(){
		public boolean verify(String hostname, SSLSession session) {
		    return true;
		}});
	    SSLContext context = SSLContext.getInstance("TLS");
	    context.init(null, new X509TrustManager[]{new X509TrustManager(){
		public void checkClientTrusted(X509Certificate[] chain,
			String authType) throws CertificateException {}
		public void checkServerTrusted(X509Certificate[] chain,
			String authType) throws CertificateException {}
		public X509Certificate[] getAcceptedIssuers() {
		    return new X509Certificate[0];
		}}}, new SecureRandom());
	    HttpsURLConnection.setDefaultSSLSocketFactory(
		    context.getSocketFactory());
	} catch (Exception e) { // should never happen
	    e.printStackTrace();
	}
    }

}

//    /////////// BEGIN stub implementations ///////////
//
//    private static class Log {
//	static void v(String tag,String message){
//	    System.err.println(tag+": "+message);
//	}
//	static void e(String tag,String message,Throwable t){
//	    System.err.println(tag+": "+message);
//	    t.printStackTrace();
//	}
//    }
//
//    private static class Handler {
//	void post(Runnable r){
//	    r.run();
//	}
//    }
//
//    public static void main(String[] args) {
//	Handler h=new Handler();
//	SimpleHttpHandler handler=new SimpleHttpHandler(h);
//	try {
//	    handler.execute("https://join.safe4kid.co.kr/couplevow/",new String[]{"method","get_pw","id","01012348202"},new SimpleHttpHandler.Callback(){
//		    @Override
//		    public void run() {
//			Log.v(LOG_TAG,"STATUS: "+getStatus());
//			Date d=new Date();
//			d.setTime(getServerTime());
//			Log.v(LOG_TAG,"DATE: "+getServerTime()+" ("+d+")");
//			Log.v(LOG_TAG,"RESP: "+getResponse());
//		    }
//		});
//	}catch(IOException e){
//	    e.printStackTrace();
//	}
//    }
//
//    /////////// END stub implementations ///////////

