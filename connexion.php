<?php
// connexion.php
// Configure ta DB ici :
$DB_HOST = 'localhost';
$DB_NAME = 'task_tracker'; // <--- assure-toi que c'est le bon nom
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // En local on affiche l'erreur ; en prod on logge et affiche un message sobre
    die('Erreur connexion DB: ' . $e->getMessage());
}

/**
 * Crée la table users si elle n'existe pas et insère un admin (haché) si aucun utilisateur présent.
 */
function ensure_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
          id INT AUTO_INCREMENT PRIMARY KEY,
          username VARCHAR(50) NOT NULL UNIQUE,
          password VARCHAR(255) NOT NULL,
          rank ENUM('admin','user') NOT NULL DEFAULT 'user',
          telephone VARCHAR(20)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Si aucun utilisateur -> création d'admin/admin123 (haché)
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $st = $pdo->prepare("INSERT INTO users (username,password,rank,telephone) VALUES (?, ?, 'admin', '')");
        $st->execute(['admin', $hash]);
    }
}
