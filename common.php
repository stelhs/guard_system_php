<?php 
    
define("EINVAL", 1); /* Inctorrect input parameters */
define("EBASE", 2); /* Database error */
define("ESQL", 3); /* SQL error */
define("ENOTUNIQUE", 4); /* Element not enique */
define("EBUSY", 5); /* Resource or device is busy */
define("ENODEV", 22);  /* No device or resourse found  */
define("ECONNFAIL", 42); /* Connection fault */
define("EPARSE", 137); /* Parsing error */


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

/**
 * Split string on words
 * @param $str - string
 * @return array of words
 */
function split_string($str)
{
    $cleaned_words = array();
    $words = split("[ \t,]", $str);
    if (!$words)
        return false;

    foreach ($words as $word) {
        $cleaned_word = trim($word);
        if ($cleaned_word == '')
            continue;
        
        $cleaned_words[] = trim($word);
    }

    return $cleaned_words;
}

function array_to_string($array) // Записать данные массива в строчку через запятую
{
    $str = '';
    $seporator = '';
    if($array)
        foreach($array as $word)
        {
            $str .= $seporator . addslashes($word);
            $seporator = ',';
        }
    return $str;
}

function string_to_array($array) // Распарсить строку в массива
{
    $arr = explode(',', $array);
    foreach($arr as $item)
        $result[$item] = $item;
        
    return $result;
}

?>