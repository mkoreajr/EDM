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
    // Login slideshow images uploaded by administrators. Stored in PostgreSQL so they persist across Render redeploys.
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_slides (id BIGSERIAL PRIMARY KEY, filename VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, image_data TEXT NOT NULL, sort_order INTEGER NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    // Admin password recovery table. This does not modify or delete business data.
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_recovery (id SMALLINT PRIMARY KEY, admin_recovery_used BOOLEAN NOT NULL DEFAULT FALSE)");
    $pdo->exec("INSERT INTO system_recovery(id, admin_recovery_used) VALUES (1, FALSE) ON CONFLICT (id) DO NOTHING");
    echo "Database schema ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Database initialization failed: ".$e->getMessage()."\n");
    exit(1);
}
