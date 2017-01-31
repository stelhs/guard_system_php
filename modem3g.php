<?php

class Modem3G {

    function send_sms($pnone_number, $text)
    {
        $cmd =  'wget --post-data ' .
                '"<?xml version="1.0" encoding="UTF-8"?>' .
                '<request>' .
                    '<Index>-1</Index>' .
                    '<Phones>' .
                        '<Phone>' . $pnone_number . '</Phone>' .
                    '</Phones>' .
                    '<Content>' . $text . '</Content>' .
                    '<Length>' . strlen($text) . '</Length>' .
                    '<Reserved>0</Reserved>' .
                    '<Date>111</Date>' .
                '</request>" ' .
                "http://192.168.1.1/api/sms/send-sms";

        return run_cmd($cmd);
    }

    function send_ussd($text)
    {
        $cmd =  'wget --post-data ' . 
                '"<?xml version="1.0" encoding="UTF-8"?>' .
                '<request>' .
                    '<content>*100#</content>' .
                    '<codeType>CodeType</codeType>' .
                '</request>" ' .
                "http://192.168.1.1/api/sms/send-ussd";

        return run_cmd($cmd);
    }

    function get_ussd_result()
    {

    }
}


?>