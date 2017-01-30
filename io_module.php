<?php
require_once("nmea0183.php");
require_once("base_sql.php");

define ("CONTROL_SOCK_FILE", '/tmp/module_io.sock');
define ("UART_DEV", '/dev/ttyUSB0');

class Io_module {
    private $io_fd;
    private $control_fd;    
    private $nmea;
    private $rx_io_messages;

    function __construct() {
        $this->nmea = new Nmea0183;
        $this->rx_io_messages = array();
        $this->rx_control_messages = array();
    }

    function open()
    {
        system('stty -F '.UART_DEV.' 9600 raw -echo');

        $this->io_fd = fopen(UART_DEV, 'w+');
        if($this->io_fd == false)
            return -ENODEV;

        @unlink(CONTROL_SOCK_FILE);
        $this->control_fd = stream_socket_server('unix://'.CONTROL_SOCK_FILE);
        if($this->control_fd == false)
            return -ENODEV;
    }

    function close()
    {
        fclose($this->io_fd);
        fclose($this->control_fd);
        @unlink(CONTROL_SOCK_FILE);
    }

    function relay_set_state($port_num, $state)
    {
        $msg = $this->nmea->create_msg('PC', 'RWS', array($port_num, $state));
        for (;;) {
            fwrite($this->io_fd, $msg);
            $resp = $this->wait_new_data(1, 'io', array('si' => 'SOP'));
            if (!is_array($resp))
                continue;

            if ($resp['type'] != 'io')
                continue;

            if ($resp['msg']['si'] != 'SOP')
                continue;

            if ($resp['msg'][0] != $port_num || $resp['msg'][1] != $state)
                continue;

            db_insert('io_output_actions', array('port' => $port_num,
                                                'state' => $state));
            return 0;
        }
    }

    function wdt_set_state($state)
    {
        $msg = $this->nmea->create_msg('PC', 'WDC', array($state));
        for (;;) {
            fwrite($this->io_fd, $msg);
            $resp = $this->wait_new_data(1, 'io', array('si' => 'WDS'));
            if (!is_array($resp))
                continue;

            if ($resp['type'] != 'io')
                continue;

            if ($resp['msg']['si'] != 'SOP')
                continue;

            if ($resp['msg'][0] != $state)
                continue;

            db_insert('io_output_actions', array('port' => $port_num,
                                                'state' => $state));
            return 0;
        }
    }

    function wdt_reset()
    {
        $msg = $this->nmea->create_msg('PC', 'WRS');
        fwrite($this->io_fd, $msg);
    }

    function wait_new_data($timeout = 0, $source = null, $filter = array())
    {
        /* attempt to search needed date into stored early list */
        if (!$source || $source == 'io') {
            foreach ($this->rx_io_messages as $idx => $msg) {
                if ($msg['si'] != $filter['si'])
                    continue;

                unset($this->rx_io_messages[$idx]);
                return array('type' => 'io', 'msg' => $msg);
            }
        }

        if (!$source || $source == 'control') {
            foreach ($this->control_rx_messages as $idx => $msg) {
                // TODO: implement filter
                unset($this->control_rx_messages[$idx]);
                return array('type' => 'control', 'msg' => $msg);
            }
        }

        $rfds = array($this->control_fd, $this->io_fd);
        $wfds = $efds = [];

        /* waiting new data */
        $num_changed = stream_select($rfds, $wfds, $efds, $timeout);
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

                    if (!$source || $source == 'io')
                        if ($msg['si'] == $filter['si'])
                            return array('type' => 'io', 'msg' => $msg);
                    
                    /* store unused received data into list */
                    $this->rx_io_messages[] = $msg;
                    return null;
                }
                break;

            /* new data was detected from control_fd */
            case $this->control_fd:

                break;
            }
        }
    }
}

?>