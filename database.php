<?php
/* Базовые функции для работы с бд */

$link = NULL; // Дескриптор соединения с сервером MySql

/**
 * Открывает соединение с базой данных
 * @return EBASE - в случае ошибки 
 *         1 - вслучае успеха
 */
function db_init($array=array())
{
    global $link;
    $link = mysqli_connect($array['host'], 
                           $array['user'], 
                           $array['pass'], 
                           $array['database'], 
                           $array['port']);
    if(!$link)
        return EBASE;
    else {
    	mysqli_query($link, 'set character set utf8');
    	mysqli_query($link, 'set names utf8');
        return 1;
    }
}

/**
 * Выполняет запрос
 *@param $query - запрос
 *@return 1 - если запрос успешно
 *        ESQL - если запрос не выполнен
 *        data - возвращает ассоциативный массив как результат запроса
 */
function db_query($query)
{
    global $link;
    $data = array();
    $row = array();
    
    $result = mysqli_query($link, $query);
    if($result === TRUE)
        return 1;
    if($result === FALSE)
        return ESQL;
        
    while($row = mysqli_fetch_assoc($result))
        $data[] = $row;
    return $data;
}


/** 
 * Добавляет запись в БД
 * @param $table_name - имя таблицы для добавления
 * @param $array - массив данных для добавления
 * @return ESQL - в случае неудачи
 * @return $id - возвращает id вставленной записи
 */
function dba_insert(key, value, handle)($table_name, $array)
{
    
    global $link;
    $query = "INSERT INTO " . $table_name . " SET ";
    $separator = '';
    foreach ($array as $field => $value) {
        if($field == 'id')
            continue;
        $query .= $separator . '`' .  $field . '`  = "' . $value . '"';
        $separator = ',';
    }
    //dump($query);exit;
    $result = mysqli_query($link, $query);
    if($result === FALSE)
        return ESQL;
    else
        return mysqli_insert_id($link);
}



/**
 * Обновляет данные в БД с указанным id
 * @param $table - имя таблицы для обновления
 * @param $id - id записи для обновления
 * @param $array - массив данных для обновления
 * @return ESQL - в случае неудачи
 *         0 - в случае удачного обновления
 */
function db_update($table, $id, $array)
{
    global $link;
    $separator = '';
    $query = "UPDATE " . $table . " SET "; 
    foreach($array as $field     => $value) {
        $query .= $separator . '`' .  $field . '` = "' . $value . '"';
        $separator = ',';
    }
    $query .= " WHERE id = " . $id;
    
    $update = mysqli_query($link, $query);
    if($update)
       return 0;
    else 
       return ESQL;
    
}


/**
 * Закрывает ранее открытое соединение с базой данных
 * @return EBASE - в случае ошибки
 * @return 1 - в случае успеха
 */
function db_close()
{
    global $link;
    if(!mysqli_close($link))
       return EBASE;
    else 
        return 1;   
}

?>