#!/usr/bin/php
<?php
require_once("common.php");
require_once("io_module.php");

$utility_name = 'send_cmd';

function print_help()
{
    echo "Usage: send_cmd <command> <args>\n" . 
             "\tcommands:\n" .
                 "\t\t relay: set relay output state. Args: port_num, 0/1\n" . 
                 "\t\t\texample: send_cmd relay 4 1\n" .

                 "\t\t wdt_on: enable hardware watchdog\n" .
                 "\t\t wdt_off: disable hardware watchdog\n" .
                 "\t\t wdt_reset: reset hardware watchdog\n" .
             "\n\n";
}



function main()
{
    global $argv;
    $io = new Io_module(null);

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

    case 'wdt_on':
        $rc = $io->wdt_set_state(1);
        if ($rc)
            printf("Can't set WDT state\n");
        return 0;

    case 'wdt_off':
        $rc = $io->wdt_set_state(0);
        if ($rc)
            printf("Can't set WDT state\n");
        return 0;

    case 'wdt_reset':
        $io->wdt_reset();
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