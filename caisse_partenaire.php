<?php
session_start();
require_once 'connexion.php';
include 'sidebar.php';

if (!isset($_SESSION['user']['username'])) {
    header("Location: login.php");
    exit();
}

// Récupérer toutes les tâches accomplies
$sql = "
    SELECT t.id, t.versement
    FROM taches t
    INNER JOIN (
        SELECT id_tache, MAX(id) as last_detail_id
        FROM detail_tache
        GROUP BY id_tache
    ) d ON t.id = d.id_tache
    INNER JOIN detail_tache dt ON dt.id = d.last_detail_id
    WHERE dt.etat = 'Accompli'
";
$stmt = $pdo->query($sql);
$taches_accomplies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul du total global des versements
$total_global = 0;
foreach ($taches_accomplies as $tache) {
    $total_global += $tache['versement'];
}

// Calcul par partenaire avec retraits pris en compte
$partenaires_caisse = [];

foreach ($taches_accomplies as $tache) {
    $id_tache = $tache['id'];
    $versement = $tache['versement'];

    // Récupérer les pourcentages + nom utilisateur des partenaires pour cette tâche
    $sql_part = "SELECT pt.id_partenaire, pt.pourcentage_partenaire, u.username AS nom_partenaire
                 FROM partenaires_tache pt
                 INNER JOIN users u ON u.id = pt.id_partenaire
                 WHERE pt.id_tache = ?";
    $stmt_part = $pdo->prepare($sql_part);
    $stmt_part->execute([$id_tache]);
    $rows_part = $stmt_part->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows_part as $row) {
        $id_partenaire = $row['id_partenaire'];
        $pourcentage = $row['pourcentage_partenaire'];
        $nom_partenaire = $row['nom_partenaire'];

        // Montant attribué
        $montant = ($versement * $pourcentage) / 100;

        if (!isset($partenaires_caisse[$id_partenaire])) {
            $partenaires_caisse[$id_partenaire] = [
                'nom' => $nom_partenaire,
                'total' => 0
            ];
        }
        $partenaires_caisse[$id_partenaire]['total'] += $montant;
    }
}

// Soustraire les retraits pour obtenir le montant réel disponible
foreach ($partenaires_caisse as $id_partenaire => $partenaire) {
    $stmt_retraits = $pdo->prepare("SELECT COALESCE(SUM(montant_retrait),0) AS total_retraits FROM retrait WHERE id_partenaire = ?");
    $stmt_retraits->execute([$id_partenaire]);
    $total_retraits = (float)$stmt_retraits->fetchColumn();

    $partenaires_caisse[$id_partenaire]['total_reel'] = $partenaire['total'] - $total_retraits;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Caisse Partenaire</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h2 class="mb-4">💰 Caisse des Partenaires</h2>

    <div class="alert alert-success">
        <strong>Total Global des Tâches Accomplies :</strong> <?= number_format($total_global, 2, ',', ' ') ?> DA
    </div>

    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Nom du Partenaire</th>
                <th>Montant Réel Disponible</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($partenaires_caisse as $id_partenaire => $partenaire): ?>
                <tr>
                    <td><?= htmlspecialchars($partenaire['nom']) ?></td>
                    <td><?= number_format($partenaire['total_reel'], 2, ',', ' ') ?> DA</td>
                    <td>
                        <a href="detail_caisse_partenaire.php?id=<?= $id_partenaire ?>" class="btn btn-info btn-sm">Détails</a>
                        <a href="retrait_partenaire.php?id=<?= $id_partenaire ?>" class="btn btn-warning btn-sm">Retrait</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
