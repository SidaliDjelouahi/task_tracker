<?php
session_start();
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user']['username'])) {
    header("Location: login.php");
    exit();
}

// Vérifier si l'id est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de tâche invalide");
}

$id_tache = (int)$_GET['id'];

try {
    $pdo->beginTransaction();

    // Récupérer tous les partenaires liés à la tâche
    $stmtPart = $pdo->prepare("SELECT id_partenaire FROM partenaires_tache WHERE id_tache = ?");
    $stmtPart->execute([$id_tache]);
    $partenaires = $stmtPart->fetchAll(PDO::FETCH_COLUMN);

    // Supprimer les retraits liés à cette tâche via ces partenaires
    if ($partenaires) {
        $in = str_repeat('?,', count($partenaires) - 1) . '?';
        $stmtDelRetrait = $pdo->prepare("DELETE FROM retrait WHERE id_partenaire IN ($in)");
        $stmtDelRetrait->execute($partenaires);
    }

    // Supprimer les partenaires associés à la tâche
    $stmt2 = $pdo->prepare("DELETE FROM partenaires_tache WHERE id_tache = :id");
    $stmt2->execute([':id' => $id_tache]);

    // Supprimer les détails de la tâche
    $stmt1 = $pdo->prepare("DELETE FROM detail_tache WHERE id_tache = :id");
    $stmt1->execute([':id' => $id_tache]);

    // Supprimer la tâche elle-même
    $stmt3 = $pdo->prepare("DELETE FROM taches WHERE id = :id");
    $stmt3->execute([':id' => $id_tache]);

    $pdo->commit();

    header("Location: liste_taches.php?msg=success");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Erreur lors de la suppression : " . $e->getMessage());
}
