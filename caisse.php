<?php
session_start();
require_once 'connexion.php';
include 'sidebar.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user']['username'])) {
    header("Location: login.php");
    exit();
}

// Récupérer la somme totale des versements dans la table taches
$sql = "SELECT SUM(versement) AS total_caisse FROM taches";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$total_caisse = $row['total_caisse'] ?? 0;

// Récupérer l’historique des tâches avec leurs versements
$sqlHist = "SELECT id, num_tache, date, montant_tache, versement FROM taches ORDER BY date DESC";
$stmtHist = $pdo->prepare($sqlHist);
$stmtHist->execute();
$taches = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Caisse</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- essentiel pour mobile -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">💰 État de la Caisse</h2>
    <div class="alert alert-success fs-4">
        Total des paiements clients (versements des tâches) : 
        <strong><?= number_format($total_caisse, 2, ',', ' ') ?> DA</strong>
    </div>

    <!-- Bouton Caisse par partenaire -->
    <div class="mb-3">
        <a href="caisse_partenaire.php" class="btn btn-primary">
            <i class="bi bi-people"></i> Caisse par partenaire
        </a>
    </div>

    <h3 class="mt-4 mb-3">📜 Historique des Versements</h3>

    <!-- Vue Desktop -->
    <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Numéro Tâche</th>
                    <th>Date</th>
                    <th>Montant Tâche</th>
                    <th>Versement</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($taches): ?>
                <?php foreach ($taches as $tache): ?>
                    <tr>
                        <td><?= htmlspecialchars($tache['num_tache']) ?></td>
                        <td><?= htmlspecialchars($tache['date']) ?></td>
                        <td><?= number_format($tache['montant_tache'], 2, ',', ' ') ?> DA</td>
                        <td><?= number_format($tache['versement'], 2, ',', ' ') ?> DA</td>
                        <td>
                            <a href="details_tache.php?id=<?= $tache['id'] ?>" class="btn btn-info btn-sm">
                                <i class="bi bi-eye"></i> Détails
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">Aucun versement enregistré</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Vue Mobile -->
    <div class="d-md-none">
        <?php if ($taches): ?>
            <?php foreach ($taches as $tache): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Tâche #<?= htmlspecialchars($tache['num_tache']) ?></h5>
                        <p class="mb-1"><strong>Date :</strong> <?= htmlspecialchars($tache['date']) ?></p>
                        <p class="mb-1"><strong>Montant :</strong> <?= number_format($tache['montant_tache'], 2, ',', ' ') ?> DA</p>
                        <p class="mb-2"><strong>Versement :</strong> <?= number_format($tache['versement'], 2, ',', ' ') ?> DA</p>
                        <a href="details_tache.php?id=<?= $tache['id'] ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-eye"></i> Détails
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">Aucun versement enregistré</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
