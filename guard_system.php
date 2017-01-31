<?php
require_once("io_module.php");


class Guard_system {
    private $sensors;
    private $io_module;
    private $db;

    function __construct($database)
    {
        $db = $database;
    }

    function open()
    {
        global $_CONFIG;
    
        $rc = db_init($_CONFIG['database_server']);
        if ($rc)
            return -ECONNFAIL;

        $this->sensors = $this->db->query_list("SELECT * FROM sensors");
        $this->io_module = new Io_module($this->db);
        $this->io_module->open();
        return 0;
    }

    function close()
    {
        db_close();
        $this->io_module->close();
    }

    function main_cycle()
    {
        $resp = $this->io_module->wait_new_data(1);
        if (is_array($resp)) {
            switch ($resp['type']) {
            case 'io':
                $this->push_io_msg($resp['msg']);
                break;
            
            case 'control':
                $this->push_control_msg($resp['msg']);
                break;
            }
        }

        $list = get_list_new_sms();
        if (is_array($list)) {
            //TODO: analysis incommong SMS 
        }
    }

    function push_io_msg($msg)
    {
        switch ($msg['si']) {
        case 'AIP': /* Action on Input Port */
            $port = $msg[0];
            $state = $msg[1];

            $this->db->insert('io_input_actions', array('port' => $port,
                                                        'state' => $state));

            $this->update_sensor($port, $state);
            break;

        case 'SOP': /* State Output Port */
            $this->db->insert('io_output_actions', array('port' => $msg[0],
                                                         'state' => $msg[1]));
            break;
        }
    }

    function push_control_msg($msg)
    {

    }

    function get_guard_state()
    {
        $data = $this->db->query("SELECT * FROM guard_actions ORDER by created DESC LIMIT 1");
        if (!$data)
            return $data;
        
        return $data['state'];
    }

    function get_sensor_locking_mode($sensor_id)
    {
        $data = $this->db->query("SELECT * FROM blocking_sensors " .
                                 "WHERE sense_id = " . $sensor_id . " " .
                                 "ORDER by created DESC LIMIT 1");
        return $data['mode'];
    }

    function update_sensor($port, $state)
    {
        $sensor = $this->sensors[$port];
        $sensor_state = ($state == $sensor['normal_state'] ? 'normal' : 'action');
        $guard_state = $this->get_guard_state();
        $action_id = $this->db->insert('sensor_actions', 
                                       array('sense_id' => $sensor['id'],
                                             'state' => $sensor_state,
                                             'guard_state' => $guard_state));

        $sense_locking_mode = get_sensor_locking_mode($sensor['id']);
        if ($sense_locking_mode == 'lock')
            return;

//        if ($guard_state == 'ready' && $sensor_state == 'action')
//            $this->do_alarm($action_id);
    }

    function do_alarm($action_id)
    {
        save_camera_images($action_id);
        $this->io_module->relay_set_state($port, $state);
    }

    function save_camera_images($action_id)
    {

    }

    function send_sms($text)
    {

    }

    function get_list_new_sms()
    {

    }
}




?>