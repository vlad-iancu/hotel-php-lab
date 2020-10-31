<?php

function jsonBody() {
    if(apache_request_headers()["Content-Type"] == "application/json")
        return json_decode(file_get_contents("php://input"));
    else {
        http_response_code(400);
        return null;
    }
}

function uuid(){
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); 
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
?>