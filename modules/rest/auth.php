<?php
// Auth functions
function CheckAuth() {
    if(isset($_GET['access_token'])) {
        session_id($_GET['access_token']);
        session_start();
        $_SESSION['mobile'] = true;

        if (!isset($_SESSION['uid'])) {
            echo json_encode(array('status' => 'FORBIDDEN', 'session_status' => 'EXPIRED'));
            session_regenerate_id();
            exit();
        }
    } else {
        echo json_encode(array('status' => 'FORBIDDEN', 'session_status' => 'NOT_LOGGED_IN'));
        exit();
    }

    global $mysqlServer, $mysqlUser, $mysqlPassword, $mysqlMainDb;
    require_once (__DIR__ . '/../../include/init.php');
}

function RequestAccessToken() {
    $require_noerrors = true;
    global $mysqlServer, $mysqlUser, $mysqlPassword, $mysqlMainDb;
    require_once (__DIR__.'/../../include/init.php');
    require_once ('modules/auth/auth.inc.php');
    require_once ('include/phpass/PasswordHash.php');
    if(!isset($_POST['uname']) || !isset($_POST['pass'])) {
        echo RESPONSE_FAILED;
        exit();
    }

    $uname = autounquote(canonicalize_whitespace($_POST['uname']));
    $pass = autounquote($_POST['pass']);

    foreach (array_keys($_SESSION) as $key)
        unset($_SESSION[$key]);

    $sqlLogin = "SELECT *
FROM user
WHERE username ";
    if (get_config('case_insensitive_usernames')) {
        $sqlLogin .= "= " . quote($uname);
    } else {
        $sqlLogin .= "COLLATE utf8_bin = " . quote($uname);
    }
    $myrow = Database::get()->querySingle($sqlLogin);
    $myrow = (array) $myrow;
    if (in_array($myrow['password'], $auth_ids)) {
        $ok = alt_login($myrow, $uname, $pass);
    } else {
        $ok = login($myrow, $uname, $pass);
    }

    if (isset($_SESSION['uid']) && $ok == 1) {
        Database::get()->query("INSERT INTO loginout (loginout.id_user, loginout.ip, loginout.when, loginout.action)
VALUES (?d, ?s, NOW(), 'LOGIN')", intval($_SESSION['uid']), $_SERVER['REMOTE_ADDR']);
        session_regenerate_id();
        echo json_encode(array('status' => 'OK', 'access_token' => session_id()));
    } else
        echo RESPONSE_FAILED;

    exit();
}
?>
