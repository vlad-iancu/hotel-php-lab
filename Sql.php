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
        if( ($types == null) != ($params == null)) {
            return null;
        }
        if($types && $params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        $execute = mysqli_stmt_execute($stmt);
        if(!$execute) {
            return false;
        }
        $result = mysqli_stmt_get_result($stmt);
        if(!$result) {
            return false;
        }
        return new Cursor($stmt, $result);
    }

?>