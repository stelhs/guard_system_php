<?php
require_once("io_module.php");
require_once("base_sql.php");



class Guard_system {
    private $sensors;
    private $io_module;

    function open()
    {
        $db_settings = array("host" => '127.0.0.1  ',
                             "user" => 'root',
                             "pass" => '13941',
                             "database" => 'automation');
    
        $rc = db_init($db_settings);
        if ($rc)
            return -ECONNFAIL;

        $rows = db_query("SELECT * FROM sensors");
        $this->sensors = array();
        foreach ($rows as $row)
            $this->sensors[$row['id']] = $row;

        $this->io_module = new Io_module;
        $this->io_module->open();
        return 0;
    }

    function close()
    {
        db_close();
        $this->io_module->close();
    }

    function do()
    {
        for (;;) {
            $resp = $this->io_module->wait_new_data(1);
            f (!is_array($resp))
                continue;

            switch ($resp['type']) {
            case 'io':
                $this->push_io_msg($resp['msg']);
                break;
            }
            case 'control':
                $this->push_control_msg($resp['msg']);
                break;
            }

            $list = get_list_new_sms();
            if (is_array($list)) {
                //TODO: analysis incommong SMS 
            }
        }
    }

    private function push_io_msg($msg)
    {
        switch ($msg['si']) {
        case 'AIP': /* Action on Input Port */
            $port = $msg[0];
            $state = $msg[1];
            db_insert('io_input_actions', array('port' => $port,
                                                'state' => $state));

            $this->update_sensor($port, $state);
            break;

        case 'SOP': /* State Output Port */
            db_insert('io_output_actions', array('port' => $msg[0],
                                                'state' => $msg[1]));
            break;
        }
    }

    private function push_control_msg($msg)
    {

    }

    private function get_guard_state()
    {
        $rows = db_query("SELECT * FROM guard_actions ORDER by created DESC LIMIT 1");
        $result = db_query($query);
        
        if ($result == FALSE)
            return -ESQL;
        else 
            return $result[0]['state'];
    }

    private function get_sensor_locking_mode($sensor_id)
    {
        $rows = db_query("SELECT * FROM blocking_sensors " .
                         "WHERE sense_id = " . $sensor_id . " " .
                         "ORDER by created DESC LIMIT 1");
        $result = db_query($query);
        
        if ($result == FALSE)
            return -ESQL;
        else 
            return $result[0]['mode'];
    }

    private function update_sensor($port, $state)
    {
        $sensor = $this->sensors[$port];
        $sensor_state = ($state == $sensor['normal_state'] ? 'normal' : 'action');
        $guard_state = $this->get_guard_state();
        $action_id = db_insert('sensor_actions', 
                               array('sense_id' => $sensor['id'],
                                     'state' => $sensor_state,
                                     'guard_state' => $guard_state));

        $sense_locking_mode = get_sensor_locking_mode($sensor['id']);
        if ($sense_locking_mode == 'lock')
            return;

//        if ($guard_state == 'ready' && $sensor_state == 'action')
//            $this->do_alarm($action_id);
    }

    private function do_alarm($action_id)
    {
        save_camera_images($action_id);
        this->io_module->relay_set_state($port, $state);
    }

    private function save_camera_images($action_id)
    {

    }

    private function send_sms($text)
    {

    }

    private function get_list_new_sms()
    {

    }
}




?>