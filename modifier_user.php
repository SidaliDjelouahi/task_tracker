<?php
session_start();
require_once 'connexion.php';
include 'sidebar.php';

// Vérifier si l'utilisateur est connecté et admin
if (!isset($_SESSION['user']['username']) || $_SESSION['user']['rank'] !== 'admin') {
    die("Accès refusé. Cette page est réservée aux administrateurs.");
}

// Vérifier si l'ID de l'utilisateur est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID utilisateur invalide.");
}

$id_user = (int)$_GET['id'];

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilisateur introuvable.");
}

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $telephone = trim($_POST['telephone']);
    $rank = $_POST['rank'];
    $password = trim($_POST['password']);

    if ($username === '' || $rank === '') {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        // Si mot de passe vide, on ne le met pas à jour
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, telephone = ?, rank = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $telephone, $rank, $hashedPassword, $id_user]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, telephone = ?, rank = ? WHERE id = ?");
            $stmt->execute([$username, $telephone, $rank, $id_user]);
        }

        $success = "Utilisateur mis à jour avec succès.";

        // Redirection automatique après 2 secondes
        echo "<meta http-equiv='refresh' content='2;url=liste_users.php'>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier utilisateur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<div class="container">
    <h1 class="mb-4">Modifier l'utilisateur : <?= htmlspecialchars($user['username']) ?></h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required autocomplete="off">
        </div>

        <div class="mb-3">
            <label class="form-label">Téléphone</label>
            <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($user['telephone']) ?>" required autocomplete="off">
        </div>

        <div class="mb-3">
            <label class="form-label">Rang</label>
            <select name="rank" class="form-select" required>
                <option value="user" <?= $user['rank'] === 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $user['rank'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="client" <?= $user['rank'] === 'client' ? 'selected' : '' ?>>Client</option>
                <option value="partenaire" <?= $user['rank'] === 'partenaire' ? 'selected' : '' ?>>Partenaire</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" name="password" class="form-control" required autocomplete="off">
        </div>

        <button type="submit" class="btn btn-success">Enregistrer</button>
        <a href="liste_users.php" class="btn btn-secondary">Retour</a>
    </form>
</div>

</body>
</html>
