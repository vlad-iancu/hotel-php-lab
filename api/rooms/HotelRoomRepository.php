<?php 

    function getRoomsForHotel($hotelId, $userId) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);

        $result = execStatementResult($conn, "SELECT viewHotelPermissionId FROM HOTEL WHERE hotelId = ?","i",$hotelId);
        $viewHotelPermission = $result->next()["viewHotelPermissionId"];

        if(!$viewHotelPermission) {
            return array("status" => "error", "message" => "You do not have the permission to view this hotel");
        }

        $result = execStatementResult($conn, "SELECT roomId as id, price as price FROM ROOM WHERE hotelId = ?","i",$hotelId);
        $rooms = array();
        while($room = $result->next()) {
            array_push($result->next());
        }

        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        return array("status" => "ok", "rooms" => $rooms);
    
    }

    function addRoomToHotel($userId, $hotelId, $name, $price) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        $result = execStatementResult($conn,"SELECT createRoomPermissionId, adminPermissionId FROM HOTEL WHERE hotelId = ?","i",$hotelId);
        $createPermissionId = $result->next()["createRoomPermissionId"];
        $adminPermissionId = $result->next()["adminPermissionId"];
        if(!$permissionId) {
            return error($conn, "The hotel does not exist");
        }

        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE userId = ? AND permissionId = ? OR permissionId = ?","iii"
        ,$userId, $createPermissionId, $adminPermissionId);
        if(!$result->next()) {
            return error($conn, "You do not have the permission to add rooms");
        }

        if(!execStatement($conn, "INSERT INTO ROOM(hotelId, name, price) VALUES(?,?,?);","isi",$hotelId, $name, $price)) {
            return error($conn, "Could not add the room to the hotel");
        }

        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
    }

    function deleteRoom() {

    }

    function updateRoomPrice() {

    }

    function setPermission() {

    }

?>