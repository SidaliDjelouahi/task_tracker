<?php
session_start();
require_once 'connexion.php';
include 'sidebar.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$id_partenaire = (int)$_SESSION['user']['id'];

// Récupérer les informations du partenaire (depuis users)
$sql_partenaire = "SELECT username AS nom FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql_partenaire);
$stmt->execute([$id_partenaire]);
$partenaire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partenaire) {
    die("Utilisateur introuvable.");
}

// Récupérer les tâches accomplies avec la part du partenaire
$sql = "
    SELECT 
        t.id AS num_tache,
        t.nom_tache,
        t.montant_tache,
        (t.montant_tache * pt.pourcentage_partenaire / 100) AS montant_partenaire
    FROM detail_tache dt
    INNER JOIN taches t ON t.id = dt.id_tache
    INNER JOIN partenaires_tache pt ON pt.id_tache = t.id
    WHERE dt.etat = 'Accompli'
      AND pt.id_partenaire = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_partenaire]);
$historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul du total pour le partenaire
$total_partenaire = 0;
foreach ($historique as $h) {
    $total_partenaire += $h['montant_partenaire'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrôle - Caisse Partenaire</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container mt-4">
    <h2>Ma caisse : <?= htmlspecialchars($partenaire['nom']) ?></h2>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>Numéro de Tâche</th>
                <th>Nom de la Tâche</th>
                <th>Montant Tâche</th>
                <th>Ma part</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($historique) > 0): ?>
                <?php foreach ($historique as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['num_tache']) ?></td>
                        <td><?= htmlspecialchars($row['nom_tache']) ?></td>
                        <td><?= number_format($row['montant_tache'], 2, ',', ' ') ?> DA</td>
                        <td><strong><?= number_format($row['montant_partenaire'], 2, ',', ' ') ?> DA</strong></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="table-success">
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong><?= number_format($total_partenaire, 2, ',', ' ') ?> DA</strong></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">Aucune tâche accomplie.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
