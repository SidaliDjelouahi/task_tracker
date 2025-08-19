<?php
require_once 'connexion.php';
include 'sidebar.php';

// Vérifier si id_partenaire est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID partenaire invalide.");
}

$id_partenaire = (int)$_GET['id'];

// Récupérer les informations du partenaire
$sql_partenaire = "SELECT username AS nom FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql_partenaire);
$stmt->execute([$id_partenaire]);
$partenaire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partenaire) {
    die("Partenaire introuvable.");
}

// Tâches accomplies - une seule ligne par tâche
$sql_taches = "
    SELECT t.id AS id_operation,
           t.date,
           t.nom_tache AS description,
           (t.montant_tache * pt.pourcentage_partenaire / 100) AS montant,
           'tache' AS type
    FROM taches t
    INNER JOIN partenaires_tache pt ON pt.id_tache = t.id
    INNER JOIN (
        SELECT id_tache, MAX(id) AS last_detail_id
        FROM detail_tache
        GROUP BY id_tache
    ) dt_last ON t.id = dt_last.id_tache
    INNER JOIN detail_tache dt ON dt.id = dt_last.last_detail_id
    WHERE dt.etat = 'Accompli'
      AND pt.id_partenaire = ?
";

// Récupérer les retraits
$sql_retraits = "
    SELECT id AS id_operation, datetime AS date, 'Retrait' AS description, montant_retrait AS montant, 'retrait' AS type
    FROM retrait
    WHERE id_partenaire = ?
";

$stmt_taches = $pdo->prepare($sql_taches);
$stmt_taches->execute([$id_partenaire]);
$taches = $stmt_taches->fetchAll(PDO::FETCH_ASSOC);

$stmt_retraits = $pdo->prepare($sql_retraits);
$stmt_retraits->execute([$id_partenaire]);
$retraits = $stmt_retraits->fetchAll(PDO::FETCH_ASSOC);

// Fusionner et trier par date
$operations = array_merge($taches, $retraits);
usort($operations, function($a, $b){
    return strtotime($a['date']) <=> strtotime($b['date']);
});

// Calcul du solde cumulatif
$solde = 0;
foreach ($operations as &$op) {
    if ($op['type'] === 'tache') {
        $solde += $op['montant'];
    } else {
        $solde -= $op['montant'];
    }
    $op['solde'] = $solde;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail Caisse Partenaire</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="container mt-4">
    <h2>Détail caisse du partenaire : <?= htmlspecialchars($partenaire['nom']) ?></h2>

    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Montant (DA)</th>
                <th>Type</th>
                <th>Solde (DA)</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($operations): ?>
                <?php foreach ($operations as $op): ?>
                    <tr>
                        <td><?= htmlspecialchars($op['date']) ?></td>
                        <td><?= htmlspecialchars($op['description']) ?></td>
                        <td><?= number_format($op['montant'],2,',',' ') ?></td>
                        <td><?= $op['type'] === 'tache' ? 'Tâche' : 'Retrait' ?></td>
                        <td><strong><?= number_format($op['solde'],2,',',' ') ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="table-success">
                    <td colspan="4"><strong>Solde final</strong></td>
                    <td><strong><?= number_format($solde,2,',',' ') ?> DA</strong></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">Aucune opération enregistrée</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="caisse_partenaire.php" class="btn btn-secondary">← Retour</a>
</body>
</html>
