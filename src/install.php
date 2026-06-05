<?php
// This is a reference PHP code for creating and filling the database.
// Add new lines and queries based on your given project.

$servername = "localhost";
// $username = "root";
// $password = "mysql";
$username = "furkan";
$password = "root";
$dbname = "MuhammetFurkanUZUN";

function summaryValue(mysqli $conn, string $sql) {
    $result = $conn->query($sql);

    if ($result === false) {
        die("Summary query error: " . $conn->error);
    }

    $row = $result->fetch_row();
    $result->free();

    return (int) ($row[0] ?? 0);
}

function printSummaryTable(array $rows) {
    echo "<h2>Inserted Data Summary</h2>";
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Table</th>";
    echo "<th>Total Rows</th>";
    echo "<th>Extra Summary</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row["table"]) . "</td>";
        echo "<td>" . htmlspecialchars((string) $row["total"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["extra"]) . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}

function printInstallSummary(mysqli $conn) {
    $commentNullParentCount = summaryValue(
        $conn,
        "SELECT COUNT(*) FROM COMMENTS WHERE parent_comment_id IS NULL"
    );
    $commentReplyCount = summaryValue(
        $conn,
        "SELECT COUNT(*) FROM COMMENTS WHERE parent_comment_id IS NOT NULL"
    );
    $commentParentCount = summaryValue(
        $conn,
        "SELECT COUNT(DISTINCT parent_comment_id) FROM COMMENTS WHERE parent_comment_id IS NOT NULL"
    );
    $commentOrphanParentCount = summaryValue(
        $conn,
        "SELECT COUNT(*)
         FROM COMMENTS child
         LEFT JOIN COMMENTS parent ON child.parent_comment_id = parent.comment_id
         WHERE child.parent_comment_id IS NOT NULL AND parent.comment_id IS NULL"
    );

    $rows = [
        [
            "table" => "USERS",
            "total" => summaryValue($conn, "SELECT COUNT(*) FROM USERS"),
            "extra" => "Created users",
        ],
        [
            "table" => "CHANNELS",
            "total" => summaryValue($conn, "SELECT COUNT(*) FROM CHANNELS"),
            "extra" => "Created channels",
        ],
        [
            "table" => "VIDEOS",
            "total" => summaryValue($conn, "SELECT COUNT(*) FROM VIDEOS"),
            "extra" => "Created videos",
        ],
        [
            "table" => "SUBSCRIPTIONS",
            "total" => summaryValue($conn, "SELECT COUNT(*) FROM SUBSCRIPTIONS"),
            "extra" => "Created subscriptions",
        ],
        [
            "table" => "COMMENTS",
            "total" => summaryValue($conn, "SELECT COUNT(*) FROM COMMENTS"),
            "extra" =>
                "NULL parent_comment_id: " . $commentNullParentCount .
                " | Replies with parent: " . $commentReplyCount .
                " | Distinct parent comments: " . $commentParentCount .
                " | Broken parent refs: " . $commentOrphanParentCount,
        ],
    ];

    printSummaryTable($rows);
}

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Delete database
$sql = "DROP DATABASE IF EXISTS " . $dbname .";";

// For checking given sql query is executed correctly
if ($conn->query($sql) === FALSE) {
    die("Error deleting database: " . mysqli_connect_error());
}

// Create database
$sql = "CREATE DATABASE " .$dbname;

// For checking given sql query is executed correctly
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . mysqli_connect_error());
}

$sql = "ALTER DATABASE MuhammetFurkanUZUN
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;";

// For checking given sql query is executed correctly
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . mysqli_connect_error());
}

//Select database
mysqli_select_db($conn, $dbname);

// Create tables
$sql = 
"CREATE TABLE USERS (
    user_id INT AUTO_INCREMENT,
    username VARCHAR(250) NOT NULL,
    password VARCHAR(250) NOT NULL,
    user_image VARCHAR(250) DEFAULT 'default',
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(250) NOT NULL UNIQUE,
    country VARCHAR(50),
    joined_on DATE DEFAULT (CURRENT_DATE),
    bio VARCHAR(250) DEFAULT 'Hello!',
    
    PRIMARY KEY(user_id)
);
CREATE TABLE CHANNELS (
    channel_id INT AUTO_INCREMENT,
	owner_id INT NOT NULL UNIQUE,
    channel_image VARCHAR(250) NOT NULL,
    name VARCHAR(250) NOT NULL UNIQUE,
    description VARCHAR(250),
    created_on DATE DEFAULT CURRENT_DATE,
    category VARCHAR(250),
    
    PRIMARY KEY(channel_id),
    FOREIGN KEY(owner_id) REFERENCES USERS(user_id)
);
CREATE TABLE VIDEOS (
    video_id INT AUTO_INCREMENT,
    channel_id INT NOT NULL,
    title VARCHAR(1024),
    description VARCHAR(1024),
    url VARCHAR(250) NOT NULL,
    duration_seconds INT,
    uploaded_at DATE DEFAULT CURRENT_DATE,
    view_count INT DEFAULT '0',
    like_count INT DEFAULT '0',
    
    PRIMARY KEY(video_id),
    FOREIGN KEY(channel_id) REFERENCES CHANNELS(channel_id)
);
CREATE TABLE SUBSCRIPTIONS (
    subscription_id INT AUTO_INCREMENT,
    subscriber_id INT NOT NULL,
    channel_id INT NOT NULL,
    subscribed_at DATE DEFAULT CURRENT_DATE,
    
    PRIMARY KEY(subscription_id),
    UNIQUE KEY unique_subscription_pair (subscriber_id, channel_id),
    FOREIGN KEY(subscriber_id) REFERENCES USERS(user_id),
    FOREIGN KEY(channel_id) REFERENCES CHANNELS(channel_id)
);
CREATE TABLE COMMENTS (
    comment_id INT AUTO_INCREMENT,
    video_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT NULL,
    body VARCHAR(250) NOT NULL,
    posted_at DATE DEFAULT CURRENT_DATE,

    PRIMARY KEY(comment_id),

    FOREIGN KEY(video_id) 
        REFERENCES VIDEOS(video_id),

    FOREIGN KEY(parent_comment_id) 
        REFERENCES COMMENTS(comment_id)
        ON DELETE SET NULL,

    FOREIGN KEY(user_id) 
        REFERENCES USERS(user_id)
);
";

// For checking given sql query is executed correctly
if($conn->multi_query($sql) === FALSE) {
    die("Error creating table: " . mysqli_connect_error());
}

do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

if ($conn->error) {
    die("SQL error: " . $conn->error);
}

// Fill tables
// ...
include "generate_data.php";

$sql = file_get_contents($sqlOutFileName);

if ($sql === false) {
    die("SQL file could not be read.");
}

if ($conn->multi_query($sql) === false) {
    die("Error executing SQL file: " . $conn->error);
}

// multi_query sonrası tüm sonuçları temizlemek gerekir
do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

if ($conn->error) {
    die("SQL error: " . $conn->error);
}

// After initialization, the login page should be displayed.
header('Location: login.php');
exit;

?>
