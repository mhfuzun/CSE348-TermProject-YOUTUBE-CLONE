<?php
$servername = "localhost";
// $username = "root";
// $password = "mysql";
$username = "furkan";
$password = "root";
$dbname = "MuhammetFurkanUZUN";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sqlCommand = "";
$executedQuery = "";
$result = null;
$errorMessage = "";
$affectedRows = null;
$isSelect = false;

if (isset($_POST["sqlTextArea"])) {
    $sqlCommand = $_POST["sqlTextArea"];
    $executedQuery = $sqlCommand;

    $result = mysqli_query($conn, $sqlCommand);
    $commandStart = strtolower(trim($sqlCommand));
    $isSelect = substr($commandStart, 0, 6) === "select";

    if ($result === false) {
        $errorMessage = mysqli_error($conn);
    } else if (!$isSelect) {
        $affectedRows = mysqli_affected_rows($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL</title>
    <link rel="stylesheet" href="resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="resources/css/mini-tube.css">
</head>
<body>
    <div class="container py-4">
        <h1 class="h3 mb-3">SQL Query Page</h1>

        <form method="post" action="sql.php">
            <div class="mb-3">
                <label for="sqlTextArea" class="form-label">SQL Query</label>
                <textarea class="form-control" id="sqlTextArea" name="sqlTextArea" rows="6"><?= $sqlCommand ?></textarea>
            </div>
            <button class="btn btn-primary" type="submit">Execute</button>
        </form>

        <?php if ($executedQuery !== "") { ?>
            <div class="mt-4">
                <h2 class="h5">Executed Query</h2>
                <pre class="sql-query-block"><?= $executedQuery ?></pre>
            </div>

            <div class="mt-3">
                <h2 class="h5">Result</h2>

                <?php if ($errorMessage !== "") { ?>
                    <div class="alert alert-danger">
                        <?= $errorMessage ?>
                    </div>
                <?php } else if ($isSelect) { ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <?php
                                $rowCount = 0;
                                $fields = mysqli_fetch_fields($result);

                                echo "<thead><tr>";
                                foreach ($fields as $field) {
                                    echo "<th>" . $field->name . "</th>";
                                }
                                echo "</tr></thead><tbody>";

                                while ($row = mysqli_fetch_assoc($result)) {
                                    if ($rowCount >= 10) {
                                        break;
                                    }

                                    echo "<tr>";
                                    foreach ($row as $key => $value) {
                                        echo "<td>" . $value . "</td>";
                                    }
                                    echo "</tr>";

                                    $rowCount++;
                                }

                                if ($rowCount === 0) {
                                    echo "<tr><td colspan=\"" . count($fields) . "\">No rows</td></tr>";
                                }

                                echo "</tbody>";
                            ?>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="alert alert-info">
                        Affected rows: <?= $affectedRows ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <script src="resources/js/bootstrap.bundle.min.js"></script>
</body>
</html>
