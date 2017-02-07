<?php
require_once("io_module.php");
require_once("strontium_tpl.php");


class Guard_system {
    private $sensors;

    private $io_module;
    private $db;
    private $modem;

    private $lamp_pid;
    private $sirena_pid;

    function __construct($database, $io_module, $modem)
    {
        $this->db = $database;
        $this->io_module = $io_module;
        $this->modem = $modem;

        $this->sensors = $this->db->query_list("SELECT * FROM sensors");
        if (!count($this->sensors))
        	throw new Exception("No sensors found in database");
    }

    function main_cycle()
    {
        for (;;) {
            $io_msg = $this->io_module->recv_new_msg(0);
            if (is_array($io_msg))
                $this->process_incomming_io_msg($io_msg);

            $list_sms = $this->modem->check_for_new_sms();
            if (is_array($list_sms)) {
                foreach($list_sms as $sms) {
                    $this->process_incomming_sms($sms);
                }
            }
            sleep(1);
        }
    }

    function parse_sms_command($text)
    {
        $words = split_string($text);
        if (!$words)
            return false;

        $cmd['cmd'] = $words[0];
        unset($words[0]);

        if (!count($words))
            return $cmd;

        foreach ($words as $word)
            $cmd['args'][] = $word;

        return $cmd;
    }


    function send_sms($tpl_name, $args = array())
    {
        global $_CONFIG;

        switch ($tpl_name) {
        case 'guard_enable':
            $ignore_sensor_list = $args;
            $tpl = new strontium_tpl;
            $tpl->open('sms_tpl/guard_enable.tpl');
            if (count($ignore_sensor_list)) {
                $tpl->assign('sensors');
                $separator = '';
                foreach ($ignore_sensor_list as $sensor_id) {
                    $sensor = $this->sensors[$sensor_id];
                    $sensor['separator'] = $separator;
                    $tpl->assign('sensor', $sensor);
                    $separator = ',';
                }
            }
            $text = $tpl->result();
            unset($tpl);
            break;

        case 'guard_disable':
            $tpl = new strontium_tpl;
            $tpl->open('sms_tpl/guard_disable.tpl');
            $text = $tpl->result();
            unset($tpl);
            break;

        case 'guard_alarm':
            $tpl = new strontium_tpl;
            $tpl->open('sms_tpl/guard_alarm.tpl');
            $tpl->assign(0, $args);
            $text = $tpl->result();
            unset($tpl);
            break;

        default:
            return -EINVAL;
        }

     //   file_put_contents('test_sms', $text);
       // return 0;

        foreach ($_CONFIG['list_access_phones'] as $phone)
            $this->modem->send_sms($phone, $text);
        return 0;
    }

    /* Set new Guard system state */
    function set_state($new_state, $method, $ignore_sensors_list = array())
    {
        $this->db->insert('guard_actions', 
                          array('state' => $new_state,
                                'method' => $method,
                                'ignore_sensors' => array_to_string($ignore_sensors_list)));
                          
        $this->db->commit();

        app_log(LOG_NOTICE, "Set state: '" . $new_state . "' by " . $method);
    }

    /* Get new Guard system state */
    function get_state()
    {
        $data = $this->db->query("SELECT * FROM guard_actions ORDER by created DESC LIMIT 1");
        if (!$data)
            return $data;
        
        if (!is_array($data) || !isset($data['state'])) {
        	$data = array();
            $data['state'] = 'sleep';
        }

        if (isset($data['ignore_sensors']) && $data['ignore_sensors']) 
            $data['ignore_sensors'] = string_to_array($data['ignore_sensors']);
        else
            $data['ignore_sensors'] = [];

        return $data;
    }

    
    function lamp_enable($timeout = 0)
    {
    	global $_CONFIG;
		kill_all($this->lamp_pid);
		
    	if (!$timeout) {
    		$this->io_module->relay_set_state($_CONFIG['guard_settings']['lamp_io_port'], 1);
    		return;
    	}
    	
        $pid = pcntl_fork();
        if ($pid == -1)
            throw new Exception("can't fork() in lamp_enable()");

        /* current process return */
        if ($pid) {
            $this->lamp_pid = $pid;
            return 0;
        }
        
		register_shutdown_function(function() {
                posix_kill(getmypid(), SIGKILL);
        });
        
        /* new process continue */
        fclose(STDERR);
        fclose(STDIN);
        fclose(STDOUT);
    	
        sleep($timeout);
        
    	$this->io_module->relay_set_state($_CONFIG['guard_settings']['lamp_io_port'], 0);
        exit(0);
    }

    function lamp_disable()
    {
    	global $_CONFIG;
    	$this->io_module->relay_set_state($_CONFIG['guard_settings']['lamp_io_port'], 0);
    	
        global $_CONFIG;
        if (!$this->lamp_pid)
            return;

        kill_all($this->lamp_pid);
        $this->lamp_pid = 0;
    	$this->io_module->relay_set_state($_CONFIG['guard_settings']['lamp_io_port'], 0);
    }
    
    function sirena_enable($sequencer = false)
    {
        global $_CONFIG;
        kill_all($this->sirena_pid);

        if (!$sequencer) {
            $this->io_module->relay_set_state($_CONFIG['guard_settings']['sirena_io_port'], 1);
            $this->sirena_pid = 0;
            return 0;
        }

        $pid = pcntl_fork();
        if ($pid == -1)
            throw new Exception("can't fork() in sirena_enable()");

        /* current process return */
        if ($pid) {
            $this->sirena_pid = $pid;
            return 0;
        }
        
		register_shutdown_function(function() {
                posix_kill(getmypid(), SIGKILL);
        });
        
        /* new process continue */
        fclose(STDERR);
        fclose(STDIN);
        fclose(STDOUT);

        foreach ($sequencer as $step) {
            $this->io_module->relay_set_state($_CONFIG['guard_settings']['sirena_io_port'], $step['state']);
                usleep($step['interval'] * 1000);
        }
        exit(0);
    }

    function sirena_disable()
    {
        global $_CONFIG;
        $this->io_module->relay_set_state($_CONFIG['guard_settings']['sirena_io_port'], 0);
        
        if (!$this->sirena_pid)
            return;

        kill_all($this->sirena_pid);
        $this->sirena_pid = 0;
        $this->io_module->relay_set_state($_CONFIG['guard_settings']['sirena_io_port'], 0);
    }


    function guard_disable($method)
    {
        app_log(LOG_NOTICE, "Guard stoped by " . $method);
        $this->sirena_enable(array(array('state' => 1, 'interval' => 200),
                                   array('state' => 0, 'interval' => 200),
                                   array('state' => 1, 'interval' => 200),
                                   array('state' => 0, 'interval' => 0),
                                        ));
        $this->send_sms('guard_disable');
        $this->set_state('sleep', $method);
    }

    function guard_enable($method)
    {
        app_log(LOG_NOTICE, "Guard closed by " . $method);

        /* check for incorrect sensor value state */
        $ignore_sensor_list = [];
        foreach ($this->sensors as $sensor) {
            if ($this->get_sensor_locking_mode($sensor['id']) == 'lock')
                continue;

            $port_state = $this->io_module->get_input_port_state($sensor['port']);
            if ($port_state != $sensor['normal_state'])
                $ignore_sensor_list[] = $sensor['id'];
        }

        if (!count($ignore_sensor_list))
            $this->sirena_enable(array(array('state' => 1, 'interval' => 200),
                                       array('state' => 0, 'interval' => 0),
                                            ));
        else
            $this->sirena_enable(array(array('state' => 1, 'interval' => 200),
                                       array('state' => 0, 'interval' => 200),
                                       array('state' => 1, 'interval' => 1000),
                                       array('state' => 0, 'interval' => 0),
                                            ));

        $this->send_sms('guard_enable', $ignore_sensor_list);
        $this->set_state('ready', $method, $ignore_sensor_list);
    }

    function process_incomming_sms($sms)
    {
        global $_CONFIG;
        $undefined_phone = 1;
        $undefined_text = 1;

        foreach ($_CONFIG['list_access_phones'] as $phone) {
            if ($phone != $sms['phone'])
                continue;

            $undefined_phone = 0;
            break;
        }

        $cmd = $this->parse_sms_command($sms['text']);
        if ($cmd && !$undefined_phone) {
            dump($cmd);
            $undefined_text = 0;

            switch ($cmd['cmd']) {
            case 'off':
                $this->guard_disable('sms');
                break;    

            case 'on':
                $this->guard_enable('sms');
                break;    

            default:
                $undefined_text = 1;
            }
        }

        $this->db->insert('incomming_sms', array('phone' => $sms['phone'],
                                                 'text' => $sms['text'],
                                                 'received_date' => $sms['date'],
                                                 'undefined_phone' => $undefined_phone,
                                                 'undefined_text' => $undefined_text));
		$this->db->commit();
    }

    function process_incomming_io_msg($msg)
    {
        switch ($msg['si']) {
        case 'AIP': /* Action on Input Port */
            $port = $msg[0];
            $state = $msg[1];

            $this->db->insert('io_input_actions', array('port' => $port,
                                                        'state' => $state));
            $this->db->commit();
            
            app_log(LOG_NOTICE, "Action on Input Port " . $port . " was registred");
            $this->analisys_sensor_changes($port, $state);
            break;

        case 'SOP': /* State Output Port */
            $this->db->insert('io_output_actions', array('port' => $msg[0],
                                                         'state' => $msg[1]));
            $this->db->commit();
            break;
        }
    }

    function get_sensor_locking_mode($sensor_id)
    {
        $data = $this->db->query("SELECT * FROM blocking_sensors " .
                                 "WHERE sense_id = " . $sensor_id . " " .
                                 "ORDER by created DESC LIMIT 1");
        return $data ? $data['mode'] : 'unlock';
    }

    function get_sensor_by_io_port($port)
    {
        if (!count($this->sensors))
            return false;

        foreach ($this->sensors as $sensor) {
            if ($sensor['port'] == $port)
                return $sensor;
        }

        return false;
    }

    function analisys_sensor_changes($port, $state)
    {
        $sensor = $this->get_sensor_by_io_port($port);
        if (!$sensor)
            return;

        printf("curr_state = %s\n", $state);

        $sensor_state = ($state == $sensor['normal_state'] ? 'normal' : 'action');
        $guard_state = $this->get_state();
        $action_id = $this->db->insert('sensor_actions', 
                                       array('sense_id' => $sensor['id'],
                                             'state' => $sensor_state,
                                             'guard_state' => $guard_state['state']));

        $this->db->commit();
                                       
        $sense_locking_mode = $this->get_sensor_locking_mode($sensor['id']);
        if ($sense_locking_mode == 'lock')
            return;
            
        if ($guard_state['ignore_sensors'])
       		foreach ($guard_state['ignore_sensors'] as $ignore_sensor_id)
       			if ($ignore_sensor_id == $sensor['id'])
       				return;

        printf("change_sensor, guard_state = %s, sensor_state = %s\n", 
                                    $guard_state['state'], $sensor_state);
                                    
        if ($guard_state['state'] == 'ready' && $sensor_state == 'action')
            $this->do_alarm($action_id, $sensor['id']);
    }

    /*
        Enable alarm
    */
    function do_alarm($action_id, $sensor_id)
    {
        global $_CONFIG;
        printf("do_alarm %d\n", $action_id);
        $this->save_camera_images($action_id);
        $this->sirena_enable(array(array('state' => 1, 'interval' => $_CONFIG['guard_settings']['sirena_timeout']),
                                   array('state' => 0, 'interval' => 0),
                                   ));

        $this->send_sms('guard_alarm', array('sensor_name' => $this->sensors[$sensor_id]['name'],
                                             'action_id' => $action_id));
    }

    function save_camera_images($action_id)
    {
        global $_CONFIG;
        printf("save_camera_images\n");

        $result = 0;
        foreach ($_CONFIG['video_cameras'] as $cam) {
            $cmd = 'ffmpeg -f video4linux2 -i ' . $cam['v4l_dev'] .
                   ' -vf scale=' . $cam['resolution'] . 
                   ' -vframes 1 ' . 
                   $_CONFIG['camera_dir'] . '/' . $action_id . '_cam_' . $cam['id'] . '.jpeg';
            $ret = run_cmd($cmd);
            $result |= $ret['rc'];
        }

        return $result;
    }

}




?>