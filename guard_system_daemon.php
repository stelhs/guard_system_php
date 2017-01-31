#!/usr/bin/php
<?php
require_once("config.php");
require_once("os.php");
require_once("database.php");
require_once("modem3g.php");
require_once("guard_system.php");

$utility_name = 'guard_system';

function error_exception($exception)
{
    echo 'Error: ' . $exception->getMessage() . "\n";
    exit;
}


function main()
{
    global $_CONFIG;
    set_exception_handler('error_exception');

    $db = new Database;
    $rc = $db->connect($_CONFIG['database_server']);
    if ($rc)
        throw new Exception("can't connect to database");

  //  $modem = new Modem3G;
    //$modem->send_sms("5051024", "бла бла тест");
   // $gs = new Guard_system($db);
   // $gs->do();


}

return main();

?>