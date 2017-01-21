<?php 
    
define("EINVAL", 1); // Ошибка во входных аргументах
define("EBASE", 2); // Ошибка связи с базой
define("ESQL", 3); // Не корректный SQL запрос
define("ENOTUNIQUE", 4); // Ошибка добавления в базу, если такая запись уже существует
define("ENODEV", 22); 
define("EPARSE", 137);


function dump($msg)
{
    print_r($msg);
}

?>