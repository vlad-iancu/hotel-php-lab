<?php 
    define("DEBUG", true);
    function info($tag, $message) {
        if(DEBUG) {
            file_put_contents("./logs/info.log", $tag."/:".$message."\n",FILE_APPEND);
        }
    }

    function debug($tag, $message) {
        file_put_contents("./logs/debug.log", $tag."/:".$message."\n",FILE_APPEND);
    }
?>