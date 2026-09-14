<?php
require __DIR__ . '/config/database.php';
$sql = file_get_contents(__DIR__ . '/database.sql');
if ($sql === false) { fwrite(STDERR, "database.sql not found\n"); exit(1); }
try {
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') $pdo->exec($statement);
    }
    echo "Database schema ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Database initialization failed: ".$e->getMessage()."\n");
    exit(1);
}
