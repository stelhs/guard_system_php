<?php 
    
define("EINVAL", 1); // Ошибка во входных аргументах
define("EBASE", 2); // Ошибка связи с базой
define("ESQL", 3); // Не корректный SQL запрос
define("ENOTUNIQUE", 4); // Ошибка добавления в базу, если такая запись уже существует
define("ENODEV", 22); 
define("ECONNFAIL", 42);
define("EPARSE", 137);


function dump($msg)
{
    print_r($msg);
}

function xml_struct_to_array($values, &$i)
{
    $child = array();
    if(isset($values[$i]['value']))
        array_push($child, $values[$i]['value']);
    
    while($i++ < (count($values) - 1)) {
        switch($values[$i]['type']) {
        case 'cdata':
            array_push($child, $values[$i]['value']);
        break;

        case 'complete':
            $name = $values[$i]['tag'];
            if(!empty($name)) {
                $data['content'] = trim((isset($values[$i]['value'])) ? $values[$i]['value'] : '');
                if(isset($values[$i]['attributes']))
                    $data['attr'] = $values[$i]['attributes'];
                $child[$name][] = $data;
            }
        break;

        case 'open':
            $name = $values[$i]['tag'];
            $size = isset($child[$name]) ? sizeof($child[$name]) : 0;
            if(isset($values[$i]['attributes']))
                $child[$name][$size]['attr'] = $values[$i]['attributes'];
            $child[$name][$size]['content'] = xml_struct_to_array($values, $i);
        break;

        case 'close':
            return $child;
        }
    }
    return $child;
}

function parse_xml($xml) // Функция конвертирует XML в массив
{
    $values = array();
    $index  = array();
    $array  = array();
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parse_into_struct($parser, $xml, $values, $index);
    xml_parser_free($parser);
    $i = 0;
    $name = $values[$i]['tag'];
    
    if(isset($values[$i]['attributes']))
        $array[$name]['attributes'] =  $values[$i]['attributes'];
        
    $array[$name]['content'] = xml_struct_to_array($values, $i);
    return $array;
}


?>