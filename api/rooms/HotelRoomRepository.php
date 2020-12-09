<?php 
    require_once './MySqlConnect.php';
    require_once './Sql.php';
    require_once './api/Utils.php';
    function getRoomsForHotel($hotelId, $userId, $page, $pageSize, $query) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        if(!hotelExists($conn, $hotelId)) {
            return error($conn, "There is no hotel with the id: $hotelId", 404);
        }
        $result = execStatementResult($conn, "SELECT viewHotelPermissionId FROM HOTEL WHERE hotelId = ?","i",$hotelId);
        $viewHotelPermission = $result->next()["viewHotelPermissionId"];

        if(!$viewHotelPermission) {
            return array("status" => "error", "message" => "You do not have the permission to view this hotel");
        }
        $limit = $pageSize;
        $offset = $pageSize * ($page - 1);
        if($query) {
           // echo "Query is set";
            $result = execStatementResult($conn, "SELECT roomId as id, price as price, name as name, MATCH(name) AGAINST(?) as relevance 
            FROM ROOM WHERE hotelId = ? ORDER BY relevance DESC LIMIT ? OFFSET ?;","siii", $query, $hotelId, $limit, $offset);
        }
        else {
           // echo "Query is not set";
            $result = execStatementResult($conn, "SELECT roomId as id, price as price, name as name
            FROM ROOM WHERE hotelId = ? LIMIT ? OFFSET ?;","iii", $hotelId, $limit, $offset);
        }
        $roomCount = execStatementResult($conn, "SELECT COUNT(*) as roomCount FROM ROOM WHERE hotelId = ?", "i", $hotelId)->next()["roomCount"];
        $rooms = array();
        while($room = $result->next()) {
            array_push($rooms, $room);
        }

        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        return array(
            "status" => "ok", 
            "rooms" => $rooms,
            "pages" => intdiv($roomCount, $limit),
            "hasMore" => $page <= intdiv($roomCount, $limit)) ;
    }

    function getRoomById($userId, $roomId) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);

        $result = execStatementResult($conn, "SELECT HOTEL.viewHotelPermissionId as pid FROM ROOM JOIN HOTEL ON ROOM.hotelId = HOTEL.hotelId WHERE ROOM.roomId = ?", "i", $roomId);
        $permissionId = $result->next()["pid"];

        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE permissionId = ? AND userId = ?","ii",$permissionId,$userId);
        if(!$result->next()) {
            return error("You are not allowed to view this room");
        }
        $result = execStatementResult($conn, "SELECT hotelId as hotel_id, price as price, roomId as id, name as name FROM ROOM WHERE roomId = ?","i",$roomId);
        $result = $result->next();

        mysqli_commit($conn);
        mysqli_autocommit($conn, true);

        $result["status"] = "ok"; $result["message"] = "Success";
        return $result;
    }

    function addRoomToHotel($userId, $hotelId, $name, $price) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        $result = execStatementResult($conn,"SELECT createRoomPermissionId, adminPermissionId FROM HOTEL WHERE hotelId = ?","i",$hotelId);
        $hotelRow = $result->next();
        $createPermissionId = $hotelRow["createRoomPermissionId"];
        $adminPermissionId = $hotelRow["adminPermissionId"];
        if(!$adminPermissionId || !$createPermissionId) {
            return error($conn, "The hotel does not exist");
        }

        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE userId = ? AND permissionId = ? OR permissionId = ?","iii",
        $userId, $createPermissionId, $adminPermissionId);
        if(!$result->next()) {
            return error($conn, "You do not have the permission to add rooms");
        }

        execStatement($conn, "INSERT INTO PERMISSION(permissionName) VALUES(?)","s","AddRoom-".$name);
        $permissionId = mysqli_insert_id($conn);

        execStatement($conn, "INSERT INTO PERMISSION_GRANT(permissionId, userId) VALUES(?,?)", $permissionId, $userId);
        if(!execStatement($conn, "INSERT INTO ROOM(hotelId, writePermissionId, deletePermissionId, cancelBookingPermissionId, price, name) VALUES(?,?,?,?,?,?);","iiiiis",
        $hotelId, $permissionId, $permissionId, $permissionId, $name, $price)) {
            return error($conn, "Could not add the room to the hotel");
        }
        $roomId = mysqli_insert_id($conn);

        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        return array(
            "status" => "ok",
            "message" => "Room added successfully",
            "room" =>  array("id" => $roomId,
                             "hotel_id" => $hotelId,
                             "name" => $name,
                             "price" => $price));
    }

    function deleteRoom($roomId) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);

        $result = execStatementResult($conn, "SELECT writePermissionId, deletePermissionId, cancelBookingPermissionId FROM ROOM WHERE roomId = ?","i",
                                    $roomId);
        $row = $result->next();
        $writePermissionId = $row["writePermissionId"];
        $deletePermissionid = $row["deletePermissionId"];
        $cancelBookingPermissionId = $row["cancelBookingPermissionId"];

        $result->close();
        execStatement($conn, "DELETE FROM ROOM WHERE roomId = ?","i",$roomId);

        cleanGhostPermission($conn, $writePermissionId);
        cleanGhostPermission($conn, $deletePermissionid);
        cleanGhostPermission($conn, $cancelBookingPermissionId);
        
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);

        return array("status" => "ok", "message" => "Room deleted successfully");

    }

    function updateRoom($userId, $roomId, $newPrice, $newName) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        $result = execStatementResult($conn, "SELECT writePermissionId as pid from ROOM WHERE roomId = ?","i",$roomId);
        $permissionId = $result->next()["pid"];
        
        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE permissionId = ? AND userId = ?","ii",$permissionId, $userId);
        if(!$result->next()) {
            return error($conn, "You are not allowes to modify this room");
        }

        execStatement($conn, "UPDATE ROOM SET price = ?, name = ? WHERE roomId = ?","isi",$newPrice,$newName,$roomId);

        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        return array("status" => "ok", "message" => "room updated successfully");
    }

    function hotelExists($conn, $hotelId) {
        $result = execStatementResult($conn, "SELECT * FROM HOTEL WHERE hotelId = ?", "i", $hotelId);
        if(!$result->next()) {
            return false;
        }
        return true;
    }

?>