<?php
$_CONFIG['work_dir'] = '/usr/local/guard_system';
$_CONFIG['camera_dir'] = '/var/spool/guard_system/images';
$_CONFIG['debug_level'] = array(LOG_ERR, LOG_WARNING, LOG_NOTICE);
$_CONFIG['database_server'] = array("host" => '127.0.0.1',
                                    "port" => '3306',
                                    "user" => 'root',
                                    "pass" => '13941',
                                    "database" => 'guard_system');

$_CONFIG['video_cameras'] = array(array('id' => 1,
                                       'v4l_dev' => '/dev/video14',
                                       'resolution' => '1280:1024'),

                                 array('id' => 2,
                                       'v4l_dev' => '/dev/video15',
                                       'resolution' => '1280:1024'));

$_CONFIG['modem'] = array('ip_addr' => '192.168.1.1');

$_CONFIG['io_module'] = array('uart_dev' => '/dev/ttyS0',
                              'uart_speed' => 9600,
						 	  'timeout' => 250,
							  'repeate_count' => 3);

$_CONFIG['list_access_phones'] = array('+375295051024', /*'+375295365072'*/);

$_CONFIG['guard_settings'] = array('sirena_io_port' => 3,
								   'lamp_io_port' => 4,
                                   'sirena_timeout' => 30000);

