<?php
session_start();
require_once 'connexion.php';
include 'sidebar.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user']['username']) || $_SESSION['user']['rank'] !== 'admin') {
    die("Accès refusé. Cette page est réservée aux administrateurs.");
}

// Vérifier si l'id de la tâche est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de tâche invalide.");
}

$id_tache = (int)$_GET['id'];

// Récupérer la tâche
$stmt = $pdo->prepare("SELECT * FROM taches WHERE id = ?");
$stmt->execute([$id_tache]);
$tache = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tache) {
    die("Tâche introuvable.");
}

// Traitement du formulaire pour mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pourcentage'])) {
    foreach ($_POST['pourcentage'] as $id_pt => $pourcentage) {
        $pourcentage = floatval($pourcentage);
        $stmt = $pdo->prepare("UPDATE partenaires_tache SET pourcentage_partenaire = ? WHERE id = ?");
        $stmt->execute([$pourcentage, $id_pt]);
    }
    $success = "✅ Pourcentages mis à jour avec succès.";
}

// Récupérer les partenaires de la tâche
$stmt = $pdo->prepare("
    SELECT pt.id, pt.id_partenaire, pt.pourcentage_partenaire, u.username
    FROM partenaires_tache pt
    LEFT JOIN users u ON pt.id_partenaire = u.id
    WHERE pt.id_tache = ?
");
$stmt->execute([$id_tache]);
$partenaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier % Partenaires - Tâche <?= htmlspecialchars($tache['nom_tache']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- important pour mobile -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="p-4">

<div class="container">
    <h1 class="mb-4 text-center">Modifier % Partenaires<br><small class="text-muted">Tâche : <?= htmlspecialchars($tache['nom_tache']) ?></small></h1>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center fw-bold"><?= $success ?></div>
    <?php endif; ?>

    <!-- Bouton ajouter partenaire -->
    <div class="mb-3 text-end">
        <a href="ajouter_partenaire.php?id_tache=<?= $id_tache ?>" class="btn btn-primary">
            + Ajouter partenaire
        </a>
    </div>

    <form method="post">
        <!-- Vue Desktop -->
        <div class="d-none d-md-block">
            <table class="table table-bordered align-middle shadow-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Partenaire</th>
                        <th style="width:200px;">Pourcentage (%)</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partenaires as $pt): ?>
                        <tr>
                            <td><?= htmlspecialchars($pt['username'] ?? 'Inconnu') ?></td>
                            <td>
                                <input type="number" step="0.01" min="0" max="100" 
                                       name="pourcentage[<?= $pt['id'] ?>]" 
                                       value="<?= htmlspecialchars($pt['pourcentage_partenaire']) ?>" 
                                       class="form-control" required>
                            </td>
                            <td class="text-center">
                                <button type="submit" class="btn btn-success btn-sm">💾 Modifier</button>
                                <a href="supprimer_partenaire.php?id=<?= $pt['id'] ?>&id_tache=<?= $id_tache ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Voulez-vous vraiment supprimer ce partenaire ?');">
                                    🗑 Supprimer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Vue Mobile -->
        <div class="d-md-none">
            <?php foreach ($partenaires as $pt): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($pt['username'] ?? 'Inconnu') ?></h5>
                        <div class="mb-2">
                            <label class="form-label">Pourcentage (%)</label>
                            <input type="number" step="0.01" min="0" max="100" 
                                   name="pourcentage[<?= $pt['id'] ?>]" 
                                   value="<?= htmlspecialchars($pt['pourcentage_partenaire']) ?>" 
                                   class="form-control" required>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success btn-sm">💾 Modifier</button>
                            <a href="supprimer_partenaire.php?id=<?= $pt['id'] ?>&id_tache=<?= $id_tache ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Voulez-vous vraiment supprimer ce partenaire ?');">
                                🗑 Supprimer
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3">
            <a href="liste_taches.php" class="btn btn-secondary">⬅ Retour</a>
        </div>
    </form>
</div>

</body>
</html>
