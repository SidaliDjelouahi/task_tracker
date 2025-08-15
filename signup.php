<?php
session_start();
require __DIR__ . '/connexion.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rank = $_POST['rank'] ?? 'user';
    $telephone = trim($_POST['telephone'] ?? '');

    if ($username !== '' && $password !== '') {
        // Vérifie si l'utilisateur existe déjà
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);

        if ($check->fetch()) {
            $error = "Nom d'utilisateur déjà pris.";
        } else {
            // Hache le mot de passe
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Insère le nouvel utilisateur
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, rank, telephone)
                VALUES (?, ?, ?, ?)
            ");
            if ($stmt->execute([$username, $hash, $rank, $telephone])) {
                $success = "Compte créé avec succès. Vous pouvez vous connecter.";
            } else {
                $error = "Erreur lors de la création du compte.";
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Inscription</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow-sm">
          <div class="card-body">
            <h1 class="h4 text-center mb-4">Créer un compte</h1>

            <?php if ($error): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
              <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post">
              <div class="mb-3">
                <label class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Téléphone</label>
                <input type="text" name="telephone" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label">Rang</label>
                <select name="rank" class="form-select">
                  <option value="user">Utilisateur</option>
                  <option value="admin">Administrateur</option>
                </select>
              </div>
              <button type="submit" class="btn btn-success w-100">S'inscrire</button>
            </form>
            <p class="mt-3 text-center">
              Déjà un compte ? <a href="login.php">Connexion</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
