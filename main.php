#!/usr/bin/php
<?php
require_once("guard_system.php");


function main()
{
    $gs = new Guard_system;
    $gs->do();
}

return main();

?>