#!/usr/bin/php
<?php
require_once("common.php");
require_once("io_module.php");


function print_help()
{
    echo "Usage: send_cmd <command> <args>\n" . 
             "\tcommands:\n" .
                 "\t\t relay: set relay output state. Args: port_num, 0/1\n" . 
                 "\t\t\texample: send_cmd relay 4 1\n\n";
}



function main()
{
    global $argv;
    $io = new Io_module;

    if (!isset($argv[1])) {
        return -1;
    }

    $cmd = $argv[1];

    $rc = $io->open();
    if ($rc) {
        printf("Can't open io_module: %d\n", $rc);
        return -ENODEV;
    }

    switch ($cmd) {
    case 'relay':
        if (!isset($argv[2]) || !isset($argv[3])) {
            printf("Invalid arguments: command arguments is not set\n");
            return -EINVAL;
        }

        $port = $argv[2];
        $state = $argv[3];

        if ($port < 1 || $port > 7) {
            printf("Invalid arguments: port is not correct. port > 0 and port <= 7\n");
            return -EINVAL;
        }

        if ($state < 0 || $state > 1) {
            printf("Invalid arguments: state is not correct. state may be 0 or 1\n");
            return -EINVAL;
        }

        $rc = $io->relay_set_state($port, $state);
        if ($rc) {
            printf("Can't set relay state\n");
        }
        $io->close();
        return 0;
    }

    $uart = fopen(UART_DEV, 'w+');
    if($uart == false) {
        printf("Can't open " . UART_DEV);
        return -ENODEV;
    }

    $send_msg = $nmea->create_msg('PC', 'RWS', array(3, 0));
    fwrite($uart, $send_msg);
    fclose($uart);

    $io->close();
    return 0;
}



$rc = main();
if ($rc) {
    print_help();
}



?>