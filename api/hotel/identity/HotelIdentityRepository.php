<?php
    require_once './MySqlConnect.php';
    require_once './Log.php';
    require_once './Sql.php';
    define("TAG", "CREATE_HOTEL");
    function createHotel($creatorId, $hotelName) {
        debug(TAG, "Creating hotel with user $creatorId and name $hotelName");
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);

        execStatement($conn, "INSERT INTO PERMISSION(permissionName) VALUES(?)", "s", "HotelAdmin");
        $permissionId = mysqli_insert_id($conn);

        execStatement($conn, "INSERT INTO PERMISSION_GRANT(userId, permissionId) VALUES(?,?);","ii",$creatorId, $permissionId);

        execStatement($conn, "INSERT INTO HOTEL(hotelName, adminPermissionId, viewHotelPermissionId, createRoomPermissionId) VALUES(?,?,1,?)",
        "sii", $hotelName, $permissionId, $permissionId);

        $hotelId = mysqli_insert_id($conn);
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        return array("status" => "ok", "message" => "hotel added successfully", "name" => $hotelName);
    }

    function renameHotel($userId, $hotelId, $newName) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);
        $permissionId = execStatementResult($conn,"SELECT adminPermissionId as pid FROM HOTEL WHERE hotelId = ?","i",$hotelId)->next()["pid"];
        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE userId = ? AND permissionId = ?","ii",$userId, $permissionId);
        if(!$result->next()) {
            return error($conn, "You are not allowed to change this hotel");
        }
        execStatement($conn, "UPDATE HOTEL SET hotelName = ? WHERE hotelId = ?", "si", $newName, $hotelId);

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
            return error($conn, "The given hotel does not exist",404);
        }

        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE permissionId = ? AND userId = ?", "ii",$adminPermission,$userId);
        $grant = $result->next();
        if(!$grant) {
            return error($conn, "You need to be an admin in order to add other admins", 401);
        }
        $result = execStatementResult($conn, "SELECT userId FROM USER WHERE email = ?", "s", $newAdminEmail);
        $newAdminId = $result->next()["userId"];

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
        $adminPermission = $result->next()["adminPermissionId"];

        $result = execStatementResult($conn, "SELECT * FROM PERMISSION_GRANT WHERE permissionId = ? AND userId = ?", "ii",$adminPermission, $userId);
        $grant = $result->next();

        if(!$grant) {
            return array("status" => "error", "message" => "You need to be an admin in order to set the hotel visibility");
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

        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        return array("status" => "ok", "message" => "Hotel visibility changed successfully");

    }

    

    function getHotelsForUser($email) {
        $conn = getMysqliConnection();
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);

        $sql = "SELECT HOTEL.hotelId as id, HOTEL.hotelName as name FROM HOTEL JOIN PERMISSION ON HOTEL.adminPermissionId = PERMISSION.permissionId
        WHERE (SELECT COUNT(*) FROM PERMISSION_GRANT JOIN USER ON PERMISSION_GRANT.userId = USER.userId
        WHERE USER.email = ? AND PERMISSION_GRANT.permissionId = HOTEL.adminPermissionId) > 0";

        $result = execStatementResult($conn, $sql, "s", $email);
        $hotels = array();
        while($row = $result->next()) {
            array_push($hotels, $row);
        }

        mysqli_commit($conn);
        mysqli_autocommit($conn, false);
        mysqli_close($conn);
        return array("status" => "ok", "hotels" => $hotels);
    }
?>