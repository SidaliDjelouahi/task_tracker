<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Accueil</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar bg-body-tertiary">
    <div class="container-fluid">
      <span class="navbar-brand mb-0 h1">Gestion des tâches</span>
      <div class="d-flex align-items-center gap-3">
        <span class="text-muted">Bonjour, <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></span>
        <a class="btn btn-outline-danger btn-sm" href="login.php?action=logout">Déconnexion</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <div class="alert alert-success">
      Connexion réussie ! Vous êtes connecté en tant que <b><?= htmlspecialchars($user['rank'], ENT_QUOTES, 'UTF-8') ?></b>.
    </div>
    <p>À partir d’ici, on pourra ajouter la page des tâches, etc.</p>
  </div>
</body>
</html>
