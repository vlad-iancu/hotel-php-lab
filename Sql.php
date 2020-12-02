<?php
    require_once './MySqlConnect.php';
    class Cursor {
        private $stmt;
        private $result;
        function __construct($st, $result) {
            $this->stmt = $st;
            $this->result = $result;
            return $this->result;
        }
        public function next() {
            $row = mysqli_fetch_assoc($this->result);
            if($row != null && $row)
                return $row;
            else {
                mysqli_stmt_close($this->stmt);
                return false;
            }
            
        }

        public function close() {
            mysqli_stmt_close($this->stmt);
        }
    }
    function execStatement($conn, $sql, $types, ...$params) {
        $stmt = mysqli_prepare($conn, $sql);
        if( ($types == null) != ($params == null)) {
            return "Mysqli error";
        }
        if($types && $params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    function execStatementResult($conn, $sql, $types, ...$params): Cursor {
        $stmt = mysqli_prepare($conn, $sql);
        /* if($types == null) echo "types are null\n";
        if($params == null) echo "params are null\n";
        else echo "params are not null\n"; 
        echo "We have :".count($params)."params"; */
        
        if( ($types == null) != ($params == null)) {
/*             echo "Both types and params are null or not, aborting $sql query";
 */            return null;
        }
        if($types != null && $params != null) {
/*             echo "Attempt to bind parameters\n";
 */            if(!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
                echo "Binding failed\n";
            }
        }
        $execute = mysqli_stmt_execute($stmt);
        if(!$execute) {
            echo "Execution failed\n";
            return false;
        }
        $result = mysqli_stmt_get_result($stmt);
        if(!$result) {
            echo "No cursor\n";
            return false;
        }
        return new Cursor($stmt, $result);
    }

?>