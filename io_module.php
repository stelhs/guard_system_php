<?php
require_once("config.php");
require_once("nmea0183.php");
require_once("os.php");


class Io_module {
    private $io_fd;
    private $nmea;
    private $rx_io_messages;
    private $db;

    function __construct($database)
    {
        $this->db = $database;
        $this->nmea = new Nmea0183;
        $this->rx_io_messages = array();
        $this->rx_control_messages = array();
    }

    function open()
    {
        global $_CONFIG;
        $ret = run_cmd('stty -F '. $_CONFIG['io_module']['uart_dev'] . ' ' .
                       $_CONFIG['io_module']['uart_speed'] . ' raw -echo');
        if ($ret['rc']) {
            app_log(LOG_ERR, 'IO_module: Can\'t set UART parameters: ' . $ret['rc']);
            return -ENODEV;
        }

        $this->io_fd = fopen($_CONFIG['io_module']['uart_dev'], 'w+');
        if($this->io_fd == false) {
            app_log(LOG_ERR, 'IO_module: Can\'t open UART tty device');
            return -ENODEV;
        }
    }

    function close()
    {
        fclose($this->io_fd);
    }

    function relay_set_state($port_num, $state)
    {
    	global $_CONFIG;
        $msg = $this->nmea->create_msg('PC', 'RWS', array($port_num, $state));
        $attempts = $_CONFIG['io_module']['repeate_count'];
        while ($attempts) {
            $attempts--;
            fwrite($this->io_fd, $msg);
            $msg = $this->recv_new_msg($_CONFIG['io_module']['timeout'],
            						   array('si' => 'SOP'));
            if (!is_array($msg))
                continue;

            if ($msg['si'] != 'SOP')
                continue;

            if ($msg[0] != $port_num || $msg[1] != $state)
                continue;
			
            if ($this->db) {
            	$this->db->insert('io_output_actions', 
            					  array('port' => $port_num,
                                        'state' => $state));
            	$this->db->commit();
            }
            return 0;
        }
        
        throw new Exception("relay_set_state(). Can't receive responce from Module_IO");
        return -EBUSY;
    }

    function get_input_port_state($port_num)
    {
    	global $_CONFIG;
        $msg = $this->nmea->create_msg('PC', 'RIP', array($port_num));
        $attempts = $_CONFIG['io_module']['repeate_count'];
        while ($attempts) {
            $attempts--;
            fwrite($this->io_fd, $msg);
            $msg = $this->recv_new_msg($_CONFIG['io_module']['timeout'],
           							   array('si' => 'AIP'));
            if (!is_array($msg))
                continue;

            if ($msg['si'] != 'AIP')
                continue;

            if ($msg[0] != $port_num)
                continue;

            if ($this->db) {
            	$this->db->insert('io_input_actions', 
            					  array('port' => $port_num,
                                        'state' => $msg[1]));
            	$this->db->commit();
            }
            
            return $msg[1];
        }

        throw new Exception("relay_set_state(). Can't receive responce from Module_IO");
        return -EBUSY;
    }

    function wdt_set_state($state)
    {
    	global $_CONFIG;
        $msg = $this->nmea->create_msg('PC', 'WDC', array($state));
        $attempts = $_CONFIG['io_module']['repeate_count'];
        while ($attempts) {
            $attempts--;
            fwrite($this->io_fd, $msg);
            $msg = $this->recv_new_msg($_CONFIG['io_module']['timeout'],
                                       array('si' => 'WDS'));
            if (!is_array($msg))
                continue;

            if ($msg['si'] != 'WDS')
                continue;

            if (!!$msg[0] != $state)
                continue;

            if ($this->db) {
                $this->db->insert('io_output_actions', 
                                  array('port' => $port_num,
                                        'state' => $state));
                $this->db->commit();
            }
            
            return 0;
        }
        return -EBUSY;
    }

    function wdt_reset()
    {
        $msg = $this->nmea->create_msg('PC', 'WRS');
        fwrite($this->io_fd, $msg);
    }

    function recv_new_msg($timeout = 0, $filter = array())
    {
        /* attempt to search needed data into stored early list */
        foreach ($this->rx_io_messages as $idx => $msg) {
            if ($filter && ($msg['si'] != $filter['si']))
                continue;

            unset($this->rx_io_messages[$idx]);
            return $msg;
        }

        $rfds = array($this->io_fd);
        $wfds = $efds = [];

        /* waiting new data */
        $num_changed = stream_select($rfds, $wfds, $efds, 0, $timeout * 1000);
        if (!$num_changed)
            return null;

        foreach ($rfds as $fd) {
            switch ($fd) {
            /* new data was detected from io_fd */
            case $this->io_fd:
                $str = fgets($this->io_fd);
                if (!$str)
                    return null;

                $len = strlen($str);
                for ($i = 0; $i < $len; $i++) {
                    $b = $str{$i};
                    $msg = $this->nmea->push_rxb($b);
                    if (!$msg)
                        continue;

                    if (!$filter || ($msg['si'] == $filter['si']))
                        return $msg;
                    
                    /* store unused received data into list */
                    $this->rx_io_messages[] = $msg;
                    return null;
                }
                break;
            }
        }
    }
}

?>