<?php

class Database {
    private $link;

    function connect($array=array())
    {
        
        $this->link = mysqli_connect($array['host'], 
                               $array['user'], 
                               $array['pass'], 
                               $array['database'], 
                               $array['port']);
        if(!$this->link) {
            msg_log(LOG_ERR, mysqli_connect_error());
            return -ESQL;
        }

    	mysqli_query($this->link, 'set character set utf8');
    	mysqli_query($this->link, 'set names utf8');
    	mysqli_query($this->link, "SET AUTOCOMMIT=1");
        return 0;
    }

    function query($query)
    {
        $data = array();
        $row = array();
        
        $result = mysqli_query($this->link, $query);

        if($result === TRUE)
            return 0;

        if($result === FALSE)
            return -ESQL;
            
        return mysqli_fetch_assoc($result);
    }

    function query_list($query)
    {
        $data = array();
        $row = array();
        
        $result = mysqli_query($this->link, $query);
        if($result === TRUE)
            return 0;

        if($result === FALSE)
            return -ESQL;
            
        $id = 0;
        while($row = mysqli_fetch_assoc($result)) {
            $id++;
            if (isset($row['id']))
                $id = $row['id'];

            $data[$id] = $row;
        }

        return $data;
    }


    function insert($table_name, $array)
    {
        $query = "INSERT INTO " . $table_name . " SET ";
        $separator = '';
        foreach ($array as $field => $value) {
            if($field == 'id')
                continue;
            $query .= $separator . '`' .  $field . '`  = "' . $value . '"';
            $separator = ',';
        }
        
        $result = mysqli_query($this->link, $query);
        if($result === FALSE)
            return -ESQL;
        
        return mysqli_insert_id($this->link);
    }


    function update($table, $id, $array)
    {
        $separator = '';
        $query = "UPDATE " . $table . " SET "; 
        foreach($array as $field     => $value) {
            $query .= $separator . '`' .  $field . '` = "' . $value . '"';
            $separator = ',';
        }
        $query .= " WHERE id = " . $id;
        
        $update = mysqli_query($this->link, $query);
        if (!$update)
           return -ESQL;
        
        return 0;
        
    }
    
    function commit()
    {
    	mysqli_commit($this->link);
    }


    function close()
    {
        if(!mysqli_close($this->link))
           return -EBASE;
        
        return 0;   
    }

}

?>