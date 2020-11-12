<?php
    require_once './MySqlConnect.php';
    require_once './Log.php';
    require_once './Sql.php';
    define("TAG", "CREATE_HOTEL");
    function createHotel($creatorId, $hotelName) {
        debug(TAG, "Creating hotel with user $creatorId and name $hotelName");
        $conn = getMysqliConnection();
        if(!mysqli_autocommit($conn, false)) {
            debug(TAG, "Connection failed");
            return error($conn);
        }
        if(!mysqli_begin_transaction($conn)) {
            return error($conn);
        }
        $permissionSql = "INSERT INTO PERMISSION(permissionName) VALUES(?);";
        $addPermission = mysqli_prepare($conn, $permissionSql);
        if(!$addPermission) {
            debug(TAG, "Preparing permission insert failed");
            return error($conn);
        }
        $permissionName = $hotelName."-Admin";
        if(!mysqli_stmt_bind_param($addPermission, "s",$permissionName)) {
            debug(TAG, "Binding params for permission insert failed".$addPermission->error);
            return error($conn);
            
        }
        if(!mysqli_stmt_execute($addPermission)) {
            debug(TAG, "Permission insert failed");
            return error($conn);
            
        }
        $permissionId = mysqli_insert_id($conn);
        debug(TAG, "Added permission with id $permissionId");
        if(!$permissionId) {
            debug(TAG, "Could not get the inserted permission id");
            return error($conn);
            
        }
        if(!mysqli_stmt_close($addPermission)) {
            return error($conn);
            
        }
        debug(TAG,"Added admin permission");
        $permissionGrantSql = "REPLACE INTO PERMISSION_GRANT VALUES(?,?);";
        $permissionGrant = mysqli_prepare($conn, $permissionGrantSql);
        if(!$permissionGrant) {
            debug(TAG, "Preparing permission grant insert failed");
            return error($conn);
        }
        if(!mysqli_stmt_bind_param($permissionGrant, "ii",$permissionId, $creatorId)) {
            debug(TAG, "Binding params for permission grant insert failed".$addPermission->error);
            return error($conn);
        }
        if(!mysqli_stmt_execute($permissionGrant)) {
            debug(TAG, "Permission grant insert failed".$addPermission->error);
            return error($conn);
        }
        if(!mysqli_stmt_close($permissionGrant)) {
            debug(TAG, "closing permission grant insert statement failed".$addPermission->error);
            return error($conn);
        }
        debug(TAG, "Granted permissionId $permissionId to userId $creatorId");
        $hotelSql = "INSERT INTO HOTEL(hotelName, adminPermissionId, viewHotelPermissionId, createRoomPermissionId) VALUES(?,?,1,?);";
        $hotel = mysqli_prepare($conn, $hotelSql);
        if(!$hotel) {
            debug(TAG, "Preparing hotel insert failed");
            return error($conn);
            
        }
        if(!mysqli_stmt_bind_param($hotel, "sii", $hotelName, $permissionId, $permissionId)) {
            debug(TAG, "Binding params for hotel insert failed");
            return error($conn);
            
        }
        if(!mysqli_stmt_execute($hotel)) {
            debug(TAG, "Hotel insert failed");
            return error($conn);
            
        }
        $hotelId = mysqli_insert_id($conn);
        if(!mysqli_stmt_close($hotel)) {
            return error($conn);
            
        }
        debug(TAG, "Created hotel");
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        $hotelArray = array("name" => $hotelName, "id" => $hotelId);
        return array("status" => "ok", "message" => "Hotel created successfully", "hotel" => $hotelArray);

    }

    function renameHotel($userId, $hotelId, $newName) {
        $conn = getMysqliConnection();
        if(!$conn) {
            return array("status" => "error", "message" => "Could not connect to the database");
        }
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);

        $getAdminSql = "SELECT adminPermissionId FROM HOTEL WHERE hotelId = ?";
        $getPermission = mysqli_prepare($conn, $getAdminSql);

        mysqli_stmt_bind_param($getPermission, "i", $hotelId);
        if(!mysqli_stmt_execute($getPermission)) {
            return error($conn, "Could not get the admin permission for this hotel");
        }
        mysqli_stmt_bind_result($getPermission, $permissionId);
        if(!mysqli_stmt_fetch($getPermission)) {
            return error($conn);
        }
        mysqli_stmt_close($getPermission);

        $checkPermissionSql = "SELECT * FROM PERMISSION_GRANT WHERE userId = ? AND permissionId = ?";
        $checkPermission = mysqli_prepare($conn, $checkPermissionSql);
        mysqli_stmt_bind_param($checkPermission, "ii", $userId, $permissionId);
        if(!mysqli_stmt_execute($checkPermission)) {
            return error($conn, "Could not check the admin permission for this hotel");
        }
        
        mysqli_stmt_bind_result($checkPermission, $resultUser, $resultPermission);
        if(!mysqli_stmt_fetch($checkPermission)) {
            echo $userId;
            return error($conn, "You do not have the permission to rename this hotel");
        }
        mysqli_stmt_close($checkPermission);

        $renameSql = "UPDATE HOTEL SET hotelName = ? WHERE hotelId = ?";
        $rename = mysqli_prepare($conn, $renameSql);
        mysqli_stmt_bind_param($rename, "si", $newName, $hotelId);
        if(!mysqli_stmt_execute($rename)) {
            return error($conn, "Could not rename the hotel");
        }
        mysqli_stmt_close($rename);

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

    function hasPermission($conn, $userId, $permissionId) {
        if(!isset($conn) || is_bool($permissionId)) {
            return -1;
        }
        if(!isset($userId) || !is_int($userId)) {
            return -1;
        }
        if(!isset($permissionId) || !is_int($permissionId)) {
            return -1;
        }
        $sql = "SELECT * FROM PERSMISSION_GRANT WHERE permissionId = ? AND userId = ?;";
        $permission = mysqli_prepare($conn, $sql);
        if(!$permission) {
            return -1;
        }
        if(!mysqli_stmt_bind_param($permission, "ii",$permissionId, $userId)) {
            mysqli_stmt_close($permission);
            return -1;
        }
        if(!mysqli_stmt_execute($permission)) {
            mysqli_stmt_close($permission);
            return -1;
        }
        if(!mysqli_stmt_bind_result($permission, $resultPermission, $resultUser)) {
            mysqli_stmt_close($permission);
            return -1;
        }
        if(!mysqli_stmt_fetch($permission)) {
            mysqli_stmt_close($permission);
            return false;
        }

        mysqli_stmt_close($permission);
        return true;


    }

    function addPermission($conn, $userId, $permissionId) {
        $sql = "INSERT INTO PERMISSION_GRANT VALUES(?,?) ON DUPLICATE KEY UPDATE;";
        $add = mysqli_prepare($conn, $sql);
        if(!$add) {
            return false;
        }
        if(!mysqli_stmt_bind_param($add, "ii", $permissionId, $userId)) {
            return false;
        }
        if(!mysqli_stmt_execute($add)) {
            return false;
        }
        if(!mysqli_stmt_close($add)) {
            return false;
        }
        
        return true;
    }

    function removePermission($conn, $userId, $permissionId) {
        if(!isset($conn) || is_bool($conn)) {
            return false;
        }
        if(!isset($userId) || !is_int($userId)) {
            return false;
        }
        if(!isset($permissionId) || !is_int($permissionId)) {
            return false;
        }
        $sql = "DELETE FROM PERMISSION_GRANT WHERE permissionId = ? AND userId = ?;";
        $add = mysqli_prepare($conn, $sql);
        if(!$add) {
            return false;
        }
        if(!mysqli_stmt_bind_param($add, "ii", $permissionId, $userId)) {
            return false;
        }
        if(!mysqli_stmt_execute($add)) {
            return false;
        }
        if(!mysqli_stmt_close($add)) {
            return false;
        }

        return true;
    }
?>