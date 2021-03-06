<?php
/**
 * Receives progress report through AJAX when viewing videos
 *
 * @author <gunther@keryx.se>
 * @version "Under construction 1"
 * @license http://www.mozilla.org/MPL/
 * @package webbteknik.nu
 * 
 */

session_start();
require_once '../../includes/loadfiles.php';

// Database settings and connection
$dbx = config::get('dbx');
// init
$dbh = keryxDB2_cx::get($dbx);

user::setSessionData();

user::requires(user::TEXTBOOK);

// Reset - if present, must be name of video
// TODO Change to use joblistID
if ( isset($_POST['reset']) ) {
    $sql = <<<SQL
        DELETE FROM `userprogress`
        WHERE joblistID = :joblistID and email = :email
SQL;
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':joblistID', $_POST['reset']);
    $stmt->bindParam(':email', $_SESSION['user']);
    $stmt->execute();
    if ( $stmt->rowCount() ) {
        echo "User progress data for video deleted. Jobnum: {$_POST['reset']}. User {$_SESSION['user']}";
    } else {
        echo "No userdata deleted. Bad post params?";
    }
    exit;
}


$reportdata = filter_var($_POST['reportdata'], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);

$reportdata = json_decode($reportdata);
// status - if present, must be enum("begun", "skipped", "finished")
// JavaScripts use the value "unset", but that means keep DB empty i.e. no record stored at all
$reportdata->status = in_array($reportdata->status, array("begun", "skipped", "finished")) ? $reportdata->status : null;

// $reportdata == null <=> TimeRanges not supported, set status only

/*
$reportdata->src must be /[a-z0-9-]+/
$reportdata->firstStop must be numeric
$reportdata->viewTotal must be numeric
$reportdata->stops must be array (at least 1 in size)
$reportdata->percentage_complete must be integer 0 <= x <= 100

*/

// Partial clone to insert into db column progressdata
if ( isset($reportdata->firstStop) ) {
    $progressdata = new StdClass();
    $progressdata->firstStop = $reportdata->firstStop;
    $progressdata->viewTotal = $reportdata->viewTotal;
    $progressdata->stops     = $reportdata->stops;
    $progressdata = json_encode($progressdata);
} else {
    $progressdata = '';
}

// First view = create row

// TODO Change this to use joblist
$sql = "SELECT * FROM userprogress WHERE email = :email AND joblistID = :joblistID";

$stmt = $dbh->prepare($sql);
$stmt->bindParam(':email', $_SESSION['user']);
$stmt->bindParam(':joblistID', $reportdata->joblistID);
$stmt->execute();

$curdata = $stmt->fetch();

// TODO Allow manual unset/reset of status (also by teacher!)
if ( empty($reportdata->status) ) {
    // No need to store any data
    exit("No data to store in DB");
}

if ( !$curdata ) {
    $sql1 = <<<SQL
        INSERT INTO userprogress (email, joblistID, progressdata, percentage_complete, status, lastupdate)
        VALUES (:email, :joblistID, :progressdata, :percentage_complete, :status, NOW())
SQL;
} else {
    $sql1 = <<<SQL
        UPDATE userprogress
        SET progressdata = :progressdata, percentage_complete = :percentage_complete,
            status = :status, lastupdate = NOW()
        WHERE email = :email AND joblistID = :joblistID
SQL;
    // To be used when a student skips or finises a video
    // This reset is necessary if teacher has previously failed student
    $sql2 = <<<SQL
        UPDATE userprogress
        SET approved = NULL
        WHERE email = :email AND joblistID = :joblistID
SQL;
}
try {
    $stmt = $dbh->prepare($sql1);
    $stmt->bindParam(':progressdata', $progressdata); // JSON-encoded
    $stmt->bindParam(':percentage_complete', $reportdata->percentage_complete);
    $stmt->bindParam(':status', $reportdata->status);
    $stmt->bindParam(':email', $_SESSION['user']);
    $stmt->bindParam(':joblistID', $reportdata->joblistID);
    $stmt->execute();
    if ( 'begun' != $reportdata->status ) {
        $stmt = $dbh->prepare($sql2);
        $stmt->bindParam(':email', $_SESSION['user']);
        $stmt->bindParam(':joblistID', $reportdata->joblistID);
        $stmt->execute();
    }
}
catch (PDOException $e) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo "Progressdata could not be loaded into database";
    $FIREPHP->log($e);
    exit;
}
echo "Progressdata saved ({$reportdata->percentage_complete} % seen - jobnum: {$reportdata->joblistID})";
