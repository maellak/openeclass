<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

echo '<head>';
    echo ' <link rel="stylesheet" href="/openeclass/template/bootstrap/CSS/bootstrap-custom.css" >';
echo '</head>';

require_once '../../include/baseTheme.php';
require_once 'work_functions.php';
require_once 'modules/group/group_functions.php';

$servername = "localhost";
$username = "openeclass";
$password = "123456";
$dbname = "openeclass";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_sql = "SELECT username FROM user";
$sql2 = "SELECT grade_comments FROM assignment_submit";

$result1 = $conn->query($sql2);
$username = $conn->query($user_sql);
$i=0;
$i++;

if (mysqli_num_rows($result1) > 0 ) {
    if (mysqli_num_rows($username) > 0 )
    
     echo "<div class='table-responsive'><table class='table-default'>
                                  <tr>
                                      <th class='text-center'>Rank</th>
                                      <th class='text-center'>Username</th>
                                      <th class='text-center'>Grade</th>
                                      <th class='text-center'>Test Passed</th>
                                        </tr>";
    // output data of each row
    while($row2 =mysqli_fetch_assoc($result1) AND $row_username =mysqli_fetch_assoc($username) AND $i++) {
        
        if ($i < 3){
             echo "<tr><th class='text-center'><img src='https://upload.wikimedia.org/wikipedia/commons/f/fb/Gold_medal_with_cup.svg'  width='30px' height='30px'> " . $i. "</th><th class='text-center'>" . $row_username["username"]. "</th><th class='text-center'>" . ($row2["grade_comments"])* 100 . "%" . "</th><th class='text-center'>" . $row2["grade_comments"]. "</th></tr>";     
         }
    else if ($i > 2 or $i < 5){
         echo "<tr><th class='text-center'><img src='https://upload.wikimedia.org/wikipedia/commons/9/98/Silver_medal_with_cup.svg'  width='30px' height='30px'> " . $i. "</th><th class='text-center'>" . $row_username["username"]. "</th><th class='text-center'>" . ($row2["grade_comments"])* 100 . "%" . "</th><th class='text-center'>" . $row2["grade_comments"]. "</th></tr>";
    }
    else if ($i > 4 or $i < 7){
         echo "<tr><th class='text-center'><img src='http://upload.wikimedia.org/wikipedia/commons/f/f3/Bronze_medal_with_cup.svg'  width='30px' height='30px'> " . $i. "</th><th class='text-center'>" . $row_username["username"]. "</th><th class='text-center'>" . ($row2["grade_comments"])* 100 . "%" . "</th><th class='text-center'>" . $row2["grade_comments"]. "</th></tr>";
    }
    
    else {
         echo "<tr><th class='text-center'>" . $i. "</th><th class='text-center'>" . $row_username["username"]. "</th><th class='text-center'>" . ($row2["grade_comments"])* 100 . "%" . "</th><th class='text-center'>" . $row2["grade_comments"]. "</th></tr>";
    }
    }
    echo "</table></div>";
}
