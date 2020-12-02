<?php
require_once './MySqlConnect.php';
require_once './Log.php';

define('AUTH_TOKEN', '0');
define('REFRESH_TOKEN', '1');
define('ANONYMUS', '1');
define('AUTHENTICATED', '2');
define('TOKEN_EXPIRATION_TIME', 24 * 60 * 60);
define('DATABASE_ERROR', "Could not connect to the database");

function register(string $userName, string $email, string $password)
{
    $passHash = password_hash($password, PASSWORD_DEFAULT);
    $conn = getMysqliConnection();
    if (!$conn) {
        return array("status" => "error", "message" => "Could not connect to the database");
    }

    mysqli_autocommit($conn, false);
    if (!mysqli_begin_transaction($conn)) {
        return error($conn, "Could not connect to the database");
    }

    $registerStatement = mysqli_prepare($conn, "INSERT INTO USER(userName, password, email) VALUES(?,?,?);");
    if (!$registerStatement) {
        return error($conn, "An error has occured");
    }
    $registerStatement->bind_param("sss", $userName, $password, $email);
    //AUTH - 0;REFRESH - 1
    if (!$registerStatement->execute()) {
        return error($conn, "Could not add the specified user");
    }

    $authToken = generateToken();
    $refreshToken = generateToken();
    if (!mysqli_stmt_close($registerStatement)) {
        return error($conn, "An error has occured");
    }

    $userId = mysqli_insert_id($conn);
    $expirationTime = time() + TOKEN_EXPIRATION_TIME;
    $tokenStatement = mysqli_prepare($conn, "INSERT INTO TOKEN VALUES ('$authToken',0,$userId, $expirationTime),('$refreshToken',1,$userId, $expirationTime)");
    if (!$tokenStatement) {
        return error("An error occured");
    }
    if (!$tokenStatement->execute()) {
        debug("REGISTER", "authToken:$authToken");
        debug("REGISTER", "refreshToken:$refreshToken");
        return error($conn, "Could not add the token");
    }
    $tokenStatement->close();
    debug(TAG, "Closed token statement");
    $permissionStatement = mysqli_prepare($conn, "INSERT INTO PERMISSION_GRANT VALUES (1, $userId),(2, $userId);");
    if (!$permissionStatement->execute()) {
        return error($conn, "Could not add the permission to user");
    }
    $permissionStatement->close();

    mysqli_commit($conn);
    mysqli_autocommit($conn, true);
    mysqli_close($conn);
    $user = array("userId" => $userId, "userName" => $userName, "email" => $email);
    return array(
        "status" => "ok",
        "message" => "Successful registration",
        "authToken" => $authToken,
        "refreshToken" => $refreshToken,
        "user" => $user);

}

function login(string $email, string $password)
{
    $conn = getMysqliConnection();
    if (!$conn) {
        return array("status" => "error", "message" => "Could not connect to the database (login) ");
    }
    mysqli_autocommit($conn, false);
    mysqli_begin_transaction($conn);

    $passHash = getPasswordHash($password);

    $credentialStatement = mysqli_prepare($conn, "SELECT userId FROM USER WHERE email=? AND password=?");
    $credentialStatement->bind_param("ss", $email, $passHash);
    $credentialStatement->bind_result($userId);
    if (!mysqli_stmt_execute($credentialStatement)) {
        return error($conn, "An error has occured ");
    }

    if (!mysqli_stmt_fetch($credentialStatement)) {
        return error($conn, "Could not login with the provided credentials ");
    }
    $credentialStatement->close();

    $authToken = generateToken();
    $refreshToken = generateToken();
    $expirationTime = time() + TOKEN_EXPIRATION_TIME;
    $tokenStatement = mysqli_prepare($conn, "INSERT INTO TOKEN VALUES (?,0,$userId, $expirationTime),(?,1,$userId, $expirationTime);");

    if (!$tokenStatement) {
        return error($conn, "Could not add the authentication tokens ");
    }
    $tokenStatement->bind_param("ss", $authToken, $refreshToken);

    if (!mysqli_stmt_execute($tokenStatement)) {
        return error($conn, "Could not add the authentication tokens ");
    }

    mysqli_commit($conn);
    mysqli_autocommit($conn, true);
    mysqli_close($conn);

    return array("status" => "ok", "message" => "Successful login", "authToken" => $authToken, "refreshToken" => $refreshToken);

}

function refresh($userId, $refreshToken)
{
    $conn = getMysqliConnection();
    echo "Entered refresh" . "\n";
    if (!$conn) {
        return array("status" => "error", "message" => "Could not connect to the database ");
    }
    if (!mysqli_autocommit($conn, false)) {
        return array("status" => "error", "message" => "An error has occured ");
    }
    if (!mysqli_begin_transaction($conn)) {
        error($conn, "An error has occured ");
    }
    if (!is_int($userId)) {
        error($conn, "userId must be an integer ");
    }
    $timestamp = time();
    $select = "SELECT userId FROM TOKEN where value = ? AND tokenType=1 AND userId = $userId AND expiration < $timestamp";
    $searchTokenStatement = mysqli_prepare($conn, $select);
    if (!$searchTokenStatement) {
        return error($conn, "An error has occured " . mysqli_error($conn));
    }
    if (!mysqli_stmt_bind_param($searchTokenStatement, "s", $refreshToken)) {
        return error($conn, "An error has occured ");
    }
    if (!mysqli_stmt_execute($searchTokenStatement)) {
        return error($conn, "An error has occured ");
    }
    if (!mysqli_stmt_bind_result($searchTokenStatement, $userId)) {
        return error($conn, "An error has occured ");
    }
    if (!mysqli_stmt_fetch($searchTokenStatement)) {
        return error($conn, "An error has occured ");
    }
    mysqli_stmt_close($searchTokenStatement);

    $authToken = generateToken();
    $expirationTime = time() + TOKEN_EXPIRATION_TIME;
    $insert = "INSERT INTO TOKEN VALUES(?,0,$userId,$expirationTime);";
    $addTokenStatement = mysqli_prepare($conn, $insert);

    if (!$addTokenStatement) {
        return error($conn, "An error has occured ");
    }
    if (!mysqli_stmt_bind_param($addTokenStatement, "s", $authToken)) {
        return error($conn, "An error has occured ");
    }
    if (!mysqli_stmt_execute($addTokenStatement)) {
        return error($conn, "An error has occured ");
    }
    if (!mysqli_commit($conn)) {
        return error($conn, "An error has occured ");
    }
    if (!mysqli_autocommit($conn, true)) {
        return error($conn, "An error has occured ");
    }
    mysqli_close($conn);
    return array("status" => "ok", "message" => "Token was refreshed successdully", "authToken" => $authToken);

}

function deleteUser($userId, $password)
{
    $conn = getMysqliConnection();
    mysqli_autocommit($conn, false);
    mysqli_begin_transaction($conn);

    //Check if the email matches the password
    $passHash = getPasswordHash($password);
    $result = execStatementResult("SELECT userId FROM USER WHERE email = ? AND password = ?","ss",$email,$passHash);
    $userRow = $result->next();
    if(!$userRow) {
        return error($conn, "Email or password incorrect", 401);
    }
    $userId = $userRow["userId"];
    //If the user exists delete all its bookings
    execStatement($conn, "DELETE FROM BOOKING WHERE userId = ?", "i", $userId);
    
    //Delete all hotels which ahve only userId as admin and their associated resources
    $hotelsSql = "DELETE HOTEL, PERMISSION FROM HOTEL JOIN PERMISSION ON HOTEL.adminPermissionId = PERMISSION.permissionId 
    WHERE 
    (SELECT COUNT(IF(PERMISSION_GRANT.userId = 1, 1, NULL)), COUNT(IF(PERMISSION_GRANT.userId != 1, 1, NULL))
        FROM PERMISSION_GRANT WHERE PERMISSION_GRANT.permissionId = HOTEL.adminPermissionId) = (1, 0)";
    execStatement($conn, $hotelsSql, "ii", $userId, $userId);

    execStatement($conn, "DELETE FROM USER WHERE userId = ?", "i", $userId);


}

function getUser($userId) {
    $conn = getMysqliConnection();
    mysqli_autocommit($conn, false);
    mysqli_begin_transaction($conn, false);

    $result = execStatementResult($conn, "SELECT userId as id, email, userName as name FROM USER WHERE userId = ?", "i", $userId);
    $row = $result->next();
    if(!$row) {
        error($conn, "Could not find the requested user", 404);
    }
    mysqli_commit($conn);
    mysqli_autocommit($conn, true);
    mysqli_close($conn);

    return array("status" => "ok", "message" => "User fetched successfully", "id" => $userId, "user_name" => $row["name"], "email" => $row["email"]);
}

function changeUserName($email, $password, $newName)
{
    $conn = getMysqliConnection();
    if (!$conn) {
        return array("status" => "error", "message" => DATABASE_ERROR);
    }
    if (!mysqli_autocommit($conn, false)) {
        return array("status" => "error", "message" => DATABASE_ERROR);
    }
    if (!mysqli_begin_transaction($conn)) {
        return array("status" => "error", "message" => DATABASE_ERROR);
    }
    $sql = "UPDATE USER SET userName = ? WHERE email = ? AND password = ?";
    $update = mysqli_prepare($conn, $sql);
    if (!$update) {
        return error($conn, "An error has occured");
    }
    $passHash = getPasswordHash($password);
    if (!mysqli_stmt_bind_param($update, "sss", $newName, $email, $passHash)) {
        return error($conn, "An error has occured");
    }
    if (!mysqli_stmt_execute($update)) {
        return error($conn, "An error has occured");
    }
    if (!mysqli_stmt_close($update)) {
        return error($conn, "An error has occured");
    }
    if (!mysqli_autocommit($conn, false)) {
        return error($conn, "An error has occured");
    }
    if (!mysqli_commit($conn)) {
        return error($conn, "An error has occured");
    }
    if (!mysqli_close($conn)) {
        return error($conn, "An error has occured");
    }
    return array("status" => "ok", "message" => "Username change successful");

}

//Continue with authorization and user endpoints

function error(mysqli $conn, $message = "An error has occured", $code = 500)
{
    mysqli_rollback($conn);
    mysqli_autocommit($conn, true);
    mysqli_close($conn);
    http_response_code($code);
    return array("status" => "error", "message" => $message." ".$conn->error);
}

function authorize()
{
    if($_SERVER["REQUEST_METHOD"] == "OPTIONS")
        error_log("You tried to authorize the CORS preflight");
    $headers = apache_request_headers();
    error_log("Authorizing".$_SERVER["REQUEST_URI"]);
    $token = explode(" ", $headers["Authorization"])[1];
    /* foreach ($headers as $header => $value) {
        echo "$header: $value <br />\n";
    }
    foreach ($_SERVER as $header => $value) {
        echo "$header: $value <br />\n";
    } */
    if(!isset($token) || $token == null || $token == "")
    $token = explode(" ", $headers["authorization"])[1];
    if(!isset($token) || $token == null || $token == "")
    $token = explode(" ",$_SERVER["HTTP_AUTHORIZATION"])[1];
    
    $conn = getMysqliConnection();
    if (!$conn) {
        debug("AUTHENTICATION", $conn->error);
        return -1;
    }
    $sql = "SELECT TOKEN.userId as userId, USER.email as email FROM TOKEN JOIN USER ON USER.userId = TOKEN.userId WHERE value = ?";
    $result = execStatementResult($conn, $sql, "s", $token);
    $userRow = $result->next();
    error_log("Token:".$token);
    error_log("User in ".$_SERVER["REQUEST_METHOD"]." auth:".print_r($userRow, true));
    if(!$userRow) {
        return -1;
    }
    
    else return $userRow;
}

function generateToken()
{
    return substr(sha1(strval(time() / rand(0, 100))), 0, 20);
}

function getPasswordHash($password)
{
    return sha1($password . "This is my secret security salt" . $password);
}
// The permission for an anonymus user has id 1 and the permission for any authenticated has id 2
?>