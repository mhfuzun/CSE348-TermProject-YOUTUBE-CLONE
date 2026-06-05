<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= View::e($title) ?></title>
    
    <link rel="stylesheet" href="resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="resources/css/mini-tube.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>
    <?php require __DIR__ . "/../pages/content/navbar.php" ?>

    <main>
        <?= $content ?>
    </main>

    <?php require __DIR__ . "/../pages/content/footer.php" ?>

    <script src="resources/js/bootstrap.bundle.min.js"></script>
    <script src="resources/js/comment-replies.js"></script>
</body>
</html>
