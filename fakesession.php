<?php
if(PHP_SAPI=='cli'){
  function fakesession_load() {
    $_SESSION=json_decode(file_get_contents("session.json"),true);
  }
  function fakesession_save() {
    if(isset($_SESSION)&&count($_SESSION)>0)file_put_contents("session.json",json_encode($_SESSION));
  }
  function onexit(){fakesession_save();}
  register_shutdown_function('onexit');

  if(file_exists("session.json")){
    @session_start();
    fakesession_load();
  }
}
