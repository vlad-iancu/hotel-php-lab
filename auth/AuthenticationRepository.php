<?php 
    require_once './MySqlConnect.php';

    define('AUTH_TOKEN','0');
    define('REFRESH_TOKEN','1');
    define('ANONYMUS','1');
    define('AUTHENTICATED','2');
    define('TOKEN_EXPIRATION_TIME',24 * 60 * 60);

    function register(string $userName,string $email,string $password) {
        $passHash = password_hash($password, PASSWORD_DEFAULT);
        $conn = getMysqliConnection();
        if(!$conn) {
            return array("status" => "error", "message"=>"Could not connect to the database line 14");
        }

        mysqli_autocommit($conn, false);
        if(!mysqli_begin_transaction($conn)) {
            return authError($conn, "Could not connect to the database line 19");
        }
        
        $registerStatement = mysqli_prepare($conn, "INSERT INTO USER(userName, password, email) VALUES(?,?,?);");
        if(!$registerStatement) {
            return authError($conn, "An error has occured line 24");
        }
        $registerStatement->bind_param("sss", $userName, $password, $email);
        //AUTH - 0;REFRESH - 1
        if(!$registerStatement->execute()) {
            return authError($conn, "Could not add the specified user line 29");
        }
        
        $authToken = generateToken();
        $refreshToken = generateToken();
        if(!mysqli_stmt_close($registerStatement)) {
            return authError($conn, "An error has occured line 35");
        }
        
        $userId = mysqli_insert_id($conn);
        $expirationTime = time() + TOKEN_EXPIRATION_TIME;
        $tokenStatement = mysqli_prepare($conn, "INSERT INTO TOKEN VALUES ('$authToken',0,$userId, $expirationTime),('$refreshToken',1,$userId, $expirationTime)");
        if(!$tokenStatement) {
            return authError("An error occured");
        }
        if(!$tokenStatement->execute()) {
            return authError($conn, "Could not add the token line 45");
        }
        $tokenStatement->close();

        $permissionStatement = mysqli_prepare($conn, "INSERT INTO PERMISSION_GRANT VALUES (1, $userId),(2, $userId);");
        if(!$permissionStatement->execute()) {
            return authError($conn, "Could not add the permission to user line 51");
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
        "user" => $user
    );

        
        
        
    }

    function login(string $email, string $password) {
        $conn = getMysqliConnection();
        if(!$conn) {
            return array("status" => "error", "message"=>"Could not connect to the database line 69");
        }
        mysqli_autocommit($conn, false);
        mysqli_begin_transaction($conn);

        $passHash = getPasswordHash($password);
    
        $credentialStatement = mysqli_prepare($conn, "SELECT userId FROM USER WHERE email=? AND password=?");
        $credentialStatement->bind_param("ss",$email,$passHash);
        $credentialStatement->bind_result($userId);
        if(!mysqli_stmt_execute($credentialStatement)) {
            return authError($conn, "An error has occured line 80");
        }
        
        if(!mysqli_stmt_fetch($credentialStatement)) {
            return authError($conn, "Could not login with the provided credentials line 84");
        }
        $credentialStatement->close();

        $authToken = generateToken();
        $refreshToken = generateToken();
        $expirationTime = time() + TOKEN_EXPIRATION_TIME;
        $tokenStatement = mysqli_prepare($conn, "INSERT INTO TOKEN VALUES (?,0,$userId, $expirationTime),(?,1,$userId, $expirationTime);");

        if(!$tokenStatement) {
            return authError($conn, "Could not add the authentication tokens line 94");
        }
        $tokenStatement->bind_param("ss",$authToken, $refreshToken);
        
        if(!mysqli_stmt_execute($tokenStatement)) {
            return authError($conn, "Could not add the authentication tokens line 99");
        }

        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);

        return array("status" => "ok", "message" => "Successful registration", "authToken" => $authToken, "refreshToken" => $refreshToken);


    }

    function refresh($userId, $refreshToken) {
        $conn = getMysqliConnection();
        echo "Entered refresh"."\n";
        if(!$conn) {
            return array("status" => "error", "message" => "Could not connect to the database line 114");    
        }
        if(!mysqli_autocommit($conn, false)) {
            return array("status" => "error", "message" => "An error has occured line 117");
        }
        if(!mysqli_begin_transaction($conn)) {
            authError($conn, "An error has occured");
        }
        if(!is_int($userId)) {
            authError($conn, "userId must be an integer");
        }
        $timestamp = time();
        $select = "SELECT userId FROM TOKEN where value = ? AND tokenType=1 AND userId = $userId AND expiration < $timestamp";
        $searchTokenStatement = mysqli_prepare($conn, $select);
        if(!$searchTokenStatement) {
            return authError($conn, "An error has occured line 125 ".mysqli_error($conn));
        }
        if(!mysqli_stmt_bind_param($searchTokenStatement, "s", $refreshToken)) {
            return authError($conn, "An error has occured line 128");
        }
        if(!mysqli_stmt_execute($searchTokenStatement)) {
            return authError($conn, "An error has occured line 131");
        }
        if(!mysqli_stmt_bind_result($searchTokenStatement, $userId)) {
            return authError($conn, "An error has occured line 134");
        }
        if(!mysqli_stmt_fetch($searchTokenStatement)) {
            return authError($conn, "An error has occured line 137");
        }
        mysqli_stmt_close($searchTokenStatement);

        
        $authToken = generateToken();
        $expirationTime = time() + TOKEN_EXPIRATION_TIME;
        $insert = "INSERT INTO TOKEN VALUES(?,0,$userId,$expirationTime);";
        $addTokenStatement = mysqli_prepare($conn, $insert);
        
        if(!$addTokenStatement) {
            return authError($conn, "An error has occured line 148");
        }
        if(!mysqli_stmt_bind_param($addTokenStatement, "s", $authToken)) {
            return authError($conn, "An error has occured line 151");
        }
        if(!mysqli_stmt_execute($addTokenStatement)) {
            return authError($conn, "An error has occured line 154");
        }
        if(!mysqli_commit($conn)) {
            return authError($conn, "An error has occured line 157");
        }
        if(!mysqli_autocommit($conn, true)) {
            return authError($conn, "An error has occured line 160");
        }
        mysqli_close($conn);
        return array("status" => "ok", "message" => "Token was refreshed successdully", "authToken" => $authToken);
        
    }


    function authError($conn, $msg) {
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        mysqli_close($conn);
        http_response_code(400);
        return array("status" => "error", "message" => $msg);
    }

    function authorize($authToken) {
        
    }

    function generateToken() {
        return base64_encode(random_bytes(32));
    }

    function getPasswordHash($password) {
        return sha1($password . "This is my secret security salt" . $password);
    }
    // The permission for an anonymus user has id 1 and the permission for any authenticated has id 2

?>