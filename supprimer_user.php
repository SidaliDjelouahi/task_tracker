<?php
session_start();
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté et admin
if (!isset($_SESSION['user']['username']) || $_SESSION['user']['rank'] !== 'admin') {
    die("Accès refusé. Cette action est réservée aux administrateurs.");
}

// Vérifier si l'ID utilisateur est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID utilisateur invalide.");
}

$id_user = (int)$_GET['id'];

// Empêcher l’admin de se supprimer lui-même
if ($id_user == $_SESSION['user']['id']) {
    die("Vous ne pouvez pas vous supprimer vous-même.");
}

// Supprimer l'utilisateur
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$id_user]);

// Rediriger vers la liste des utilisateurs avec message optionnel
header("Location: liste_users.php");
exit();
