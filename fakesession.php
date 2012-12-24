<?php
if(PHP_SAPI=='cli'){

  define('FAKESESSIONFILE',getenv("phpfakesession")?getenv("phpfakesession"):'session.json');

  function fakesession_load() {
    $_SESSION=json_decode(file_get_contents(FAKESESSIONFILE),true);
  }
  function fakesession_save() {
    if(isset($_SESSION)&&count($_SESSION)>0)file_put_contents(FAKESESSIONFILE,json_encode($_SESSION));
  }
  function onexit(){fakesession_save();}
  register_shutdown_function('onexit');

  if(file_exists(FAKESESSIONFILE)){
    @session_start();
    fakesession_load();
  }

}
