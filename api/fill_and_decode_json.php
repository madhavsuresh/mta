<?php
    function fill_and_decode_json($default_json, $passed_json){
        $default_array = json_decode($default_json, $assoc = true);
        $passed_array = json_decode($passed_json,$assoc = true);
        foreach($passed_array as $key => $value) {
            $default_array[$key] = $value;    
        }
        #D
    return $default_array;
    }  
?>
