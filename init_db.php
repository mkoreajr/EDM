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
    // Admin password recovery table. This does not modify or delete business data.
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_recovery (id SMALLINT PRIMARY KEY, admin_recovery_used BOOLEAN NOT NULL DEFAULT FALSE)");
    $pdo->exec("INSERT INTO system_recovery(id, admin_recovery_used) VALUES (1, FALSE) ON CONFLICT (id) DO NOTHING");
    echo "Database schema ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Database initialization failed: ".$e->getMessage()."\n");
    exit(1);
}
