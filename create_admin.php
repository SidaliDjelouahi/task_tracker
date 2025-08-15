<?php
require __DIR__ . '/connexion.php';
try {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username,password,rank,telephone) VALUES ('admin', :h, 'admin', '') ON DUPLICATE KEY UPDATE password = :h, rank='admin'");
    $stmt->execute([':h' => $hash]);
    echo "Admin créé/mis à jour avec succès (admin / admin123).";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
