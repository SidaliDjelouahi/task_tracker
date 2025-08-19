<?php
session_start();
require_once 'connexion.php';
include 'sidebar.php';

// Vérifier si l'utilisateur est connecté et admin
if (!isset($_SESSION['user']['username']) || $_SESSION['user']['rank'] !== 'admin') {
    die("Accès refusé. Cette page est réservée aux administrateurs.");
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $telephone = trim($_POST['telephone']);
    $password = $_POST['password'];
    $rank = $_POST['rank'];

    if ($username === '' || $password === '' || $rank === '') {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        // Vérifier que le username n'existe pas déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Ce nom d'utilisateur existe déjà.";
        } else {
            // Hash du mot de passe
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insérer l'utilisateur
           $stmt = $pdo->prepare("INSERT INTO users (username, telephone, password, `rank`) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $telephone, $hash, $rank]);


            $success = "Utilisateur ajouté avec succès.";

            // Redirection automatique après 2 secondes
            echo "<meta http-equiv='refresh' content='2;url=liste_users.php'>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter utilisateur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<div class="container">
    <h1 class="mb-4">Ajouter un nouvel utilisateur</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Username *</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username ?? '') ?>" required autofocus autocomplete="off">
        </div>

        <div class="mb-3">
            <label class="form-label">Téléphone</label>
            <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($telephone ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Mot de passe *</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Rang *</label>
            <select name="rank" class="form-select" required>
                <option value="user" <?= (isset($rank) && $rank === 'user') ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= (isset($rank) && $rank === 'admin') ? 'selected' : '' ?>>Admin</option>
                <option value="partenaire" <?= (isset($rank) && $rank === 'partenaire') ? 'selected' : '' ?>>Partenaire</option>
            </select>
        </div>


        <button type="submit" class="btn btn-success">Ajouter</button>
        <a href="liste_users.php" class="btn btn-secondary">Retour</a>
    </form>
</div>

</body>
</html>
