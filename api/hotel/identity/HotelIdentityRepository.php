<?php
    require_once './MySqlConnect.php';
    require_once './Log.php';
    require_once './Sql.php';
    require_once './api/Utils.php';
    require_once './api/rooms/HotelRoomRepository.php';
    define("TAG", "DELETE_HOTEL");
    function createHotel($creatorId, $hotelName) {
        debug(TAG, "Creating hotel with user $creatorId and name $hotelName");
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        if(!$conn) {
            return error($conn, "Could not connect to the database", 500);
        }
        $success = execStatement($conn, "INSERT INTO PERMISSION(permissionName) VALUES(?)", "s", "HotelAdmin");
        if(!$success) {
            return error($conn, "Could not add the admin permission for the hotel", 500);
        }
        $permissionId = mysqli_insert_id($conn);
        if(!$permissionId) {
            return error($conn, "Could not get the admin permission for the hotel", 500);
        }

        $success = execStatement($conn, "INSERT INTO PERMISSION_GRANT(userId, permissionId) VALUES(?,?);","ii",$creatorId, $permissionId);
        if(!$success || !mysqli_affected_rows($conn)) {
            echo $permissionId.",";
            echo $creatorId;
            return error($conn, "Could not grant the admin permission to the user", 500);
        }

        $success = execStatement($conn, "INSERT INTO HOTEL(hotelName, adminPermissionId, viewHotelPermissionId, createRoomPermissionId) VALUES(?,?,1,?)",
        "sii", $hotelName, $permissionId, $permissionId);
        if(!$success) {
            return error($conn, "Hotel already exists", 400);
        }

        $hotelId = mysqli_insert_id($conn);
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        return array("status" => "ok", "message" => "hotel added successfully", "name" => $hotelName);
    }

    function deleteHotel($userId, $hotelId) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        echo $userId."\n";
        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE permissionId = (SELECT adminPermissionId FROM HOTEL WHERE hotelId = ?) AND userId = ?", "ii", $hotelId, $userId);
        $row = $result->next();
        if(!$row) {
            return error($conn, "You are not allowed to delete a hotel you don't own", 401);
        }
        $adminPermission = $row["permissionId"];
        debug(TAG, "Authorized: Admin permission is $adminPermission");
        execStatement($conn, "DELETE FROM ROOM WHERE hotelId = ?", "i", $hotelId);
        $result = execStatementResult($conn, "SELECT permissionId FROM WORKER_GROUP WHERE hotelId = ?", "i", $hotelId);
        $groupIds = array();
        while($row = $result->next()) {
            array_push($row["permissionId"]);
        }
        foreach($groupIds as $groupPermission) {
            execStatement($conn, "DELETE FROM PERMISSION WHERE permissionId = ? ", "i", $groupPermission);
        }
        execStatement($conn, "DELETE FROM WORKER_GROUP WHERE hotelId = ?", "i", $hotelId);
        if(!$result = execStatement($conn, "DELETE FROM HOTEL WHERE hotelId = ?", "i", $hotelId)) {
            return error($conn, "The requested hotel does not exist", 404);
        }
        execStatement($conn, "DELETE FROM PERMISSION_GRANT WHERE permissionId = ?", "i", $adminPermission);
        execStatement($conn, "DELETE FROM PERMISSION WHERE permissionId = ?","i", $adminPermission);
        
        mysqli_commit($conn);
        mysqli_autocommit($conn, false);
        mysqli_close($conn);
        return array("status" => "ok", "message" => "Hotel deleted successfully");
    }

    function renameHotel($userId, $hotelId, $newName) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        $permissionId = execStatementResult($conn,"SELECT adminPermissionId as pid FROM HOTEL WHERE hotelId = ?","i",$hotelId)->next()["pid"];
        if(!$permissionId) {
            return error($conn, "There is no hotel with the given id", 404);
        }
        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE userId = ? AND permissionId = ?","ii",$userId, $permissionId);
        if(!$result->next()) {
            return error($conn, "You are not allowed to change this hotel", 403);
        }
        $success = execStatement($conn, "UPDATE HOTEL SET hotelName = ? WHERE hotelId = ?", "si", $newName, $hotelId);
        if(!$success) {
            return error($conn, "Could not update the hotel name", 500);
        }
        if(mysqli_affected_rows($conn) == 0) {
            return error($conn, "There is no hotel with the given id", 404);
        }
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        return array("status" => "ok", "message" => "Hotel renamed successfully");

    }

    function addHotelAdmin($userId, $hotelId, $newAdminEmail) {
        $conn = getMysqliConnection();
        if(!$conn) {
            http_response_code(500);
            return array("status" => "error", "An error has occured");
        }
        mysqli_autocommit(false);
        mysqli_begin_transaction($conn);

        $result = execStatementResult($conn, "SELECT adminPermissionId FROM HOTEL WHERE hotelId = ?", "i", $hotelId);
        $adminPermission = $result->next()["adminPermissionId"];
        if(!$adminPermission) {
            return error($conn, "Theere is no hotel with id $hotelId",404);
        }

        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE permissionId = ? AND userId = ?", "ii",$adminPermission,$userId);
        $grant = $result->next();
        if(!$grant) {
            return error($conn, "You need to be an admin in order to add other admins", 403);
        }
        $result = execStatementResult($conn, "SELECT userId FROM USER WHERE email = ?", "s", $newAdminEmail);
        $row = $result->next();
        $newAdminId = $row["userId"];
        if(!$newAdminId) {
            return error($conn, "There is no user with the email $newAdminEmail", 404);
        }
        

        $result = execStatement($conn, "INSERT INTO PERMISSION_GRANT(userId, permissionId) VALUES(?,?)","ii",$newAdminId,$adminPermission);
        if(!$result) {
            return error($conn, "Could not grant the permission", 500);
        }

        mysqli_commit($conn);
        mysqli_autocommit(true);
        mysqli_close($conn);
        return array("status" => "ok", "message" => "Hotel admin added successfully");
    }

    function setHotelVisibility($userId, $hotelId, $visibility) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        $result = execStatementResult($conn, "SELECT adminPermissionId FROM HOTEL WHERE hotelId = ?","i",$hotelId);
        $row = $result->next();
        $adminPermission = $row["adminPermissionId"];
        if(!$adminPermission) {
            return error($conn, "There is no hotel with id $userId", 404);
        }
        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE permissionId = ? AND userId = ?", "ii",$adminPermission, $userId);
        $grant = $result->next();
        if(!$grant) {
            return error($conn, "You need to be an admin in order to set the hotel visibility", 403);
        }

        $visibilityPermissionId = 0;
        switch($visibility) {
            case "anonymus": $visibilityPermissionId = 1; break;
            case "authenticated": $visibilityPermissionId = 2; break;
            default: $visibilityPermissionId = false;
        }

        if(!$visibilityPermissionId) {
            return error($conn, "field visibility must be either anonymus or authenticated");
        }

        execStatement($conn, "UPDATE HOTEL SET viewHotelPermissionId = ? WHERE hotelId = ?","ii",$visibilityPermissionId, $hotelId);
        if(mysqli_affected_rows($conn) == 0) {
            return error($conn, "The respective hotel was not updated", 500);
        }

        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        return array("status" => "ok", "message" => "Hotel visibility changed successfully");
    }

    

    function getHotelsForUser($userId, $query, $page, $pageSize) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        /* if(!userExists($conn, $email)) {
            return error($conn, "There is no user with email: $email", 404);
        } */
        error_log("Hotels for user: $userId");
        $offset = ($page - 1) * $pageSize;
        $limit = $pageSize;
        if($query != "") {
            $sql = "SELECT HOTEL.hotelId as id, HOTEL.hotelName as name, MATCH(HOTEL.hotelName) AGAINST(?) as relevance FROM HOTEL JOIN PERMISSION ON HOTEL.adminPermissionId = PERMISSION.permissionId
            WHERE (SELECT COUNT(*) FROM PERMISSION_GRANT JOIN USER ON PERMISSION_GRANT.userId = USER.userId
            WHERE USER.userId = ? AND PERMISSION_GRANT.permissionId = HOTEL.adminPermissionId) > 0 ORDER BY relevance DESC LIMIT ? OFFSET ?";

            $result = execStatementResult($conn, $sql, "siii",$query, $userId, $limit, $offset);
        }
        else {
            $sql = "SELECT HOTEL.hotelId as id, HOTEL.hotelName as name FROM HOTEL JOIN PERMISSION ON HOTEL.adminPermissionId = PERMISSION.permissionId
            WHERE (SELECT COUNT(*) FROM PERMISSION_GRANT JOIN USER ON PERMISSION_GRANT.userId = USER.userId
            WHERE USER.userId = ? AND PERMISSION_GRANT.permissionId = HOTEL.adminPermissionId) > 0 LIMIT ? OFFSET ?";

            $result = execStatementResult($conn, $sql, "iii", $userId, $limit, $offset);
        }
        $hotels = array();
        $row = $result->next();
        while($row ) {
            array_push($hotels, $row);
            $row = $result->next();
        }
        $totalHotels = execStatementResult($conn, "SELECT COUNT(*) as count FROM HOTEL", null)->next()["count"]; //bullshit
        $numberOfPages = intdiv($totalHotels, $limit);
        if($totalHotels % $limit != 0)
            $numberOfPages++;
        mysqli_commit($conn);
        mysqli_autocommit($conn, false);
        mysqli_close($conn);
        error_log("Hotel Count:".count($hotels));
        return array(
            "status" => "ok",
            "hotels" => $hotels,
            "pages" => $numberOfPages,
            "hasMore" => count($hotels) >= $limit
        );
    }

    function userExists($conn, $key) {
        $result = null;
        if(is_int($key))
            $result = execStatementResult($conn, "SELECT * FROM USER WHERE userId = ?", "i", $key);
        if(is_string($key))
            $result = execStatementResult($conn, "SELECT * FROM USER WHERE email = ?", "s", $key);
        if(!$result->next())
            return false;

        return true;
    }

?>