<?php
/** @var string $pageTitle */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - UniLink</title>
    <link rel="icon" type="image/png" href="img/LogoProyecto.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <?php
    $cssPath = __DIR__ . '/../../css/styles.css';
    $cssVersion = file_exists($cssPath) ? (string) filemtime($cssPath) : (string) time();
    ?>
    <link rel="stylesheet" href="css/styles.css?v=<?= e($cssVersion) ?>">
</head>
<body data-auth="<?= isLoggedIn() ? '1' : '0' ?>">
