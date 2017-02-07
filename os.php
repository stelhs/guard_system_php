<?php



/**
 * Logging function
 * @param $msg_level LOG_ERR or LOG_WARNING or LOG_NOTICE
 * @param $text - error description
 */

function msg_log($msg_level, $text)
{
    global $_CONFIG, $utility_name;

    $enable = false;
    foreach ($_CONFIG['debug_level'] as $level)
        if ($level == $msg_level)
            $enable = true;

    if (!$enable)
        return;

    syslog($msg_level, $utility_name . ': ' . $text);
    switch ($msg_level)
    {
        case LOG_WARNING:
            echo $utility_name . ': Warning: ' . $text . "\n";
            break;

        case LOG_NOTICE:
            echo $utility_name . ': ' . $text . "\n";
            break;

        case LOG_ERR:
            echo $utility_name . ': Error: ' . $text . "\n";
            break;
    }
}



/**
 * Run command in console and return output
 * @param $cmd - command
 * @param bool $fork - true - run in new thread (not receive results), false - run in current thread
 * @param $stdin_data - optional data direct to stdin
 * @param $print_stdout - optional flag indicates that all output from the process should be printed
 * @return array with keys: rc and log
 */
function run_cmd($cmd, $fork = false, $stdin_data = '', $print_stdout = false)
{
    msg_log(LOG_NOTICE, 'run cmd: ' . $cmd);

    if ($fork == true)
    {
        $pid = pcntl_fork();
        if ($pid == -1)
            throw new Exception("can't fork() in run_cmd()");

        if ($pid) // Current process return
            return;

        // new process continue
        fclose(STDERR);
        fclose(STDIN);
        fclose(STDOUT);
    }

    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
    );

    $fd = proc_open('/bin/bash', $descriptorspec, $pipes);
    if ($fd == false)
        throw new Exception("proc_open() error in run_cmd()");

    $fd_write = $pipes[0];
    $fd_read = $pipes[1];

    fwrite($fd_write, $cmd . " 2>&1;\n");

    if ($stdin_data)
        fwrite($fd_write, $stdin_data);

    fclose($fd_write);

    $log = '';
    while($str = fgets($fd_read))
    {
        $log .= $str;
        if ($print_stdout)
            echo $str;
    }

    fclose($fd_read);
    $rc = proc_close($fd);
    if ($rc == -1)
        throw new Exception("proc_close() error in run_cmd()");

    if ($fork == true)
        exit;

    return array('log' => trim($log), 'rc' => $rc);
}

/**
 * get list of children PID
 * @param $parent_pid
 * @return array of children PID or false
 */
function get_child_pids($parent_pid)
{
    $ret = run_cmd("ps -ax --format '%P %p'");
    $rows = explode("\n", $ret['log']);
    if (!$rows)
        throw new Exception("incorrect output from command: ps -ax --format '%P %p'");

    $pid_list = array();

    foreach ($rows as $row)
    {
        preg_match('/([0-9]+)[ ]+([0-9]+)/s', $row, $matched);
        if (!$matched)
            continue;

        $ppid = $matched[1];
        $pid = $matched[2];
        $pid_list[$ppid][] = $pid;
    }

    if (!isset($pid_list[$parent_pid]))
        return false;

    return $pid_list[$parent_pid];
}


/**
 * Kill all proceses
 * @param $kill_pid
 */
function kill_all($kill_pid)
{
    $child_pids = get_child_pids($kill_pid);
    if ($child_pids)
        foreach ($child_pids as $child_pid)
            kill_all($child_pid);

    run_cmd('kill -9 ' . $kill_pid);
    msg_log(LOG_NOTICE, "killed PID: " . $kill_pid);
}


?>