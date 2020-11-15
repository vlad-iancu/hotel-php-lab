<?php
    require_once './Sql.php';
    require_once './MySqlConnect.php';
    function cleanGhostPermission($conn, $permissionId) {
            $result = execStatementResult($conn, "SELECT * FROM ROOM WHERE writePermissionId = ? OR deletePermissionId = ? OR cancelBookingPermissionId = ?",
            "iii",$permissionId, $permissionId, $permissionId);
            $isInRooms = $result->next();
            $result->close();

            $result = execStatementResult($conn, "SELECT * FROM HOTEL WHERE adminPermissionId = ? OR createRoomPermissionId = ?",
            "ii",$permissionId, $permissionId);
            $isInHotel = $result->next();
            $result->close();

            $result = execStatementResult($conn, "SELECT * FROM WORKER_GROUP WHERE permissionId = ?", "i", $permissionId);
            $isInGroups = $result->next();
            $result->close();
            
            if(!($isInRooms || $isInHotel || $isInGroups)) {
                execStatement($conn, "DELETE FROM PERMISSION_GRANT WHERE permissionId = ?","i",$permissionId);
                execStatement($conn, "DELETE FROM PERMISSION WHERE permissionId = ?","i",$permissionId);
            }
    }

    function inTransaction($block) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        $result = $block($conn);
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        return $result;
    }

?>