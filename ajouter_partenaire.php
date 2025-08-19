<?php
session_start();
require_once 'connexion.php';
include 'sidebar.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user']['username']) || $_SESSION['user']['rank'] !== 'admin') {
    die("Accès refusé. Cette page est réservée aux administrateurs.");
}

// Vérifier si l'id de la tâche est fourni
if (!isset($_GET['id_tache']) || !is_numeric($_GET['id_tache'])) {
    die("ID de tâche invalide.");
}

$id_tache = (int)$_GET['id_tache'];

// Récupérer les utilisateurs disponibles
$stmt = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_partenaire = $_POST['id_partenaire'] ?? null;

    if ($id_partenaire && is_numeric($id_partenaire)) {
        // Vérifier si déjà lié
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM partenaires_tache WHERE id_tache = ? AND id_partenaire = ?");
        $stmt->execute([$id_tache, $id_partenaire]);
        $existe = $stmt->fetchColumn();

        if ($existe) {
            $error = "⚠️ Ce partenaire est déjà lié à cette tâche.";
        } else {
            // Insertion
            $stmt = $pdo->prepare("INSERT INTO partenaires_tache (id_tache, id_partenaire, pourcentage_partenaire, payement_partenaire) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_tache, $id_partenaire, 0, 'non payé']);

            $success = "✅ Partenaire ajouté avec succès.";

            // Redirection JS après 2 secondes
            echo "<script>
                setTimeout(function(){
                    window.location.href = 'modifier_pourcentage_partenaire.php?id=$id_tache';
                }, 2000);
            </script>";
        }
    } else {
        $error = "⚠️ Veuillez sélectionner un partenaire.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter Partenaire à la tâche</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- important pour mobile -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
<div class="container">
    <div class="card shadow-lg rounded-4">
        <div class="card-header bg-primary text-white text-center">
            <h3 class="mb-0">Ajouter un Partenaire à la tâche</h3>
        </div>
        <div class="card-body">

            <?php if ($error): ?>
                <div class="alert alert-warning text-center fw-bold"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success text-center fw-bold"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <!-- Vue Desktop -->
                <div class="d-none d-md-block">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nom d'utilisateur</th>
                                <th>Sélectionner</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($utilisateurs as $u): ?>
                            <tr>
                                <td><?= (int)$u['id'] ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td>
                                    <input type="radio" name="id_partenaire" value="<?= (int)$u['id'] ?>" required>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Vue Mobile -->
                <div class="d-md-none">
                    <?php foreach ($utilisateurs as $u): ?>
                        <div class="card mb-2 shadow-sm">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1"><?= htmlspecialchars($u['username']) ?></h6>
                                    <small>ID: <?= (int)$u['id'] ?></small>
                                </div>
                                <div>
                                    <input type="radio" name="id_partenaire" value="<?= (int)$u['id'] ?>" required>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="modifier_pourcentages.php?id=<?= $id_tache ?>" class="btn btn-secondary">⬅ Retour</a>
                    <button type="submit" class="btn btn-success">💾 Ajouter</button>
                </div>
            </form>

        </div>
    </div>
</div>
</body>
</html>
