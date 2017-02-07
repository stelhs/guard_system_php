#!/usr/bin/php
<?php
require_once("config.php");
require_once("common.php");
require_once("os.php");
require_once("database.php");
require_once("modem3g.php");
require_once("guard_system.php");

$utility_name = 'guard_system';

function error_exception($exception)
{
    app_log(LOG_ALERT, $exception->getMessage());
    exit;
}


function app_log($type, $text)
{
	global $db;
    msg_log($type, $text);
	$db_type = '';
	
    switch ($type) {
	case LOG_ALERT:
		$db_type = 'urgent'; 
		break;
	case LOG_ERR:
		$db_type = 'error'; 
		break;
	case LOG_WARNING:
		$db_type = 'warning'; 
		break;
	case LOG_NOTICE:
		$db_type = 'notice'; 
		break;
	default:
		return;
	}
	
	$db->insert('app_logs', array('text' => 'app_crash: ' . $text,
								  'type' => $db_type));	
}



function main()
{
    global $_CONFIG, $db;
    set_exception_handler('error_exception');

    $db = new Database;
    $rc = $db->connect($_CONFIG['database_server']);
    if ($rc)
        throw new Exception("can't connect to database");

    $io_module = new Io_module($db);
    $rc = $io_module->open();
    if ($rc)
        throw new Exception("can't open Module I/O");

    $modem = new Modem3G($_CONFIG['modem']['ip_addr']);

    $gs = new Guard_system($db, $io_module, $modem);
    $state = $gs->get_state();
    app_log(LOG_NOTICE, 'Guard system started. State:' . $state['state'] . "\n");
    
    $gs->main_cycle();
}

return main();

?>