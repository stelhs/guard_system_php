<?php
require_once("common.php");

define("NMEA_MSG_MAX_LEN", 48); /* NMEA message max length */

class Nmea0183 {
    private $cnt_msg_rx;
    private $rx_carry;
    private $buf_len;
    private $buf;

    public $cnt_rx_bytes;
    public $cnt_rx_overflow;

    function __construct() {
        $this->cnt_msg_rx = 0;
        $this->rx_carry = 0;
        $this->buf_len = -1;
        $this->buf = "";

        $this->cnt_rx_bytes = 0;
        $this->cnt_rx_overflow = 0;
    }

    function push_rxb($rxb)
    {
        $this->cnt_rx_bytes++;

        switch ($rxb) {
        case '$':
            $this->rx_carry = 0;
            $this->buf_len = 0;
            $this->buf = "";
            return;

        case "\r":
        case "\n":
            if ($this->rx_carry || $this->buf_len == -1)
                return;

            $this->cnt_msg_rx++;
            $this->rx_carry = 1;
            $this->buf_len = -1;
            return $this->parse();

        default:
            $this->rx_carry = 0;
            if ($this->buf_len == -1) {
                $this->buf = "";
                return;
            }

            if ($this->buf_len >= NMEA_MSG_MAX_LEN) {
                $this->buf_len = -1;
                $this->cnt_rx_overflow++;
                return;
            }

            $this->buf .= $rxb;
            return;
        }
    }

    function create_msg($ti, $si, $args = array())
    {
        $msg = $ti . $si;

        foreach ($args as $arg)
            $msg .= ',' . $arg;
        
        $check_sum = $this->calc_checksum($msg);
        $msg .= '*' . dechex($check_sum) . "\r\n";
        $msg = '$' . $msg;
        return $msg;
    }

    private function calc_checksum($msg_buf)
    {
        $sum = 0;
        $len = strlen($msg_buf);

        for ($i = 0; $i < $len; $i++) {
            $sym = substr($msg_buf, $i, 1);
            $sum += ord($sym);
        }

        return $sum & (0xFF);
    }

    private function parse()
    {
        $crc = NULL;
        /* check for check sum */
        if (strstr($this->buf, '*')) {            
            $rows = explode('*', $this->buf);
                if (count($rows) > 2)
                    return -EPARSE;
            
            $msg_buf = $rows[0];
            $rx_check_sum = NULL;
            $rc = sscanf($rows[1], "%x", $rx_check_sum);
            if ($rc != 1)
                    return -EPARSE;

            $check_sum = $this->calc_checksum($msg_buf);
            if ($rx_check_sum != $check_sum)
                return -EPARSE;                
        } else
            $msg_buf = $this->buf;

        $rows = explode(',', $msg_buf);
        if (count($rows) < 1)
            return -EPARSE;

        $first = 1;
        $nmea_msg = array();
        foreach ($rows as $row) {
            if ($first) {
                $first = 0;
                $nmea_msg['ti'] = substr($row, 0, 2);
                $nmea_msg['si'] = substr($row, 2, 3);
                continue;
            }

            $nmea_msg[] = $row;
        }

        return $nmea_msg;
    }
}

?>