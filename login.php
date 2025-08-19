<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require __DIR__ . '/connexion.php';

// S'assure que la table users existe et qu'un admin par défaut est créé la toute première fois
ensure_schema($pdo);

// Déconnexion si demandé
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Veuillez saisir l'utilisateur et le mot de passe.";
    } else {
        $st = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        $user = $st->fetch();

        if (!$user) {
            $error = "Utilisateur introuvable.";
        } else {
            // Vérification mot de passe (haché)
            if (password_verify($password, $user['password'])) {
                // succès -> session et redirection
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'rank' => $user['rank'] ?? 'user'
                ];
                // redirection fiable vers liste_taches.php (même dossier)
                $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $target = ($base === '' || $base === '.') ? '/liste_taches.php' : $base . '/liste_taches.php';
                header('Location: ' . $target);
                exit;
            } else {
                $error = "Mot de passe incorrect.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h1 class="h4 text-center mb-3">Se connecter</h1>

            <?php if ($error): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
              <div class="mb-3">
                <label class="form-label">Utilisateur</label>
                <input name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" class="form-control" required autofocus>
              </div>
              <div class="mb-3">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <button class="btn btn-primary w-100">Connexion</button>
            </form>

            <!-- Lien vers l'inscription -->
            <p class="text-center mt-3">
              <a href="signup.php">Créer un compte</a>
            </p>

            <!-- <p class="text-muted small mt-3 mb-0">Démo : <b>admin</b> / <b>admin123</b></p> -->
            <hr>
            <a class="btn btn-outline-secondary w-100" href="login.php?action=logout">Déconnexion</a>
          </div>
        </div>
        <p class="text-center text-muted small mt-3">Si la base est vide, l'admin est créé automatiquement.</p>
      </div>
    </div>
  </div>
</body>
</html>
