#!/usr/bin/php
<?php
require_once("io_module.php");



function main()
{
    $io = new Io_module;
    $io->open();

    $gs = new Guard_system;

    for (;;) {
        $resp = $io->wait_new_data(1);
        f (!is_array($resp))
            continue;

        switch ($resp['type']) {
        case 'io':
            $gs->push_io_msg($resp['msg']);
            break;
        }
        case 'control':
            $gs->push_control_msg($resp['msg']);
            break;
        }
    }
}

return main();

?>