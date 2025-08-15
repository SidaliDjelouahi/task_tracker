<?php
session_start();
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user']['username'])) {
    header("Location: login.php");
    exit();
}

// Récupération des tâches (sans filtre côté serveur)
$sql = "
    SELECT t.id, t.num_tache, t.nom_tache,
           dt.etat AS dernier_etat,
           pt.etat_payement,
           u.username AS dernier_partenaire
    FROM taches t
    LEFT JOIN (
        SELECT d1.id_tache, d1.etat
        FROM detail_tache d1
        INNER JOIN (
            SELECT id_tache, MAX(date) AS max_date
            FROM detail_tache
            GROUP BY id_tache
        ) d2 ON d1.id_tache = d2.id_tache AND d1.date = d2.max_date
    ) dt ON t.id = dt.id_tache
    LEFT JOIN (
        SELECT pt1.id_tache, pt1.id_partenaire, pt1.etat_payement
        FROM partenaires_tache pt1
        INNER JOIN (
            SELECT id_tache, MAX(id) AS last_id
            FROM partenaires_tache
            GROUP BY id_tache
        ) pt2 ON pt1.id_tache = pt2.id_tache AND pt1.id = pt2.last_id
    ) pt ON t.id = pt.id_tache
    LEFT JOIN users u ON pt.id_partenaire = u.id
    ORDER BY t.id DESC
";

$stmt = $pdo->query($sql);
$taches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des tâches</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .highlight { background-color: #ffff99; }
    </style>
</head>
<body class="p-4">

<div class="container">
    <h1 class="mb-4">Liste des tâches</h1>

    <!-- Bouton ajouter -->
    <a href="ajouter_tache.php" class="btn btn-primary mb-3">➕ Ajouter nouvelle tâche</a>

    <!-- Recherche instantanée -->
    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Tapez pour filtrer toutes les colonnes...">

    <!-- Tableau des tâches -->
    <table class="table table-bordered table-striped" id="tasksTable">
        <thead>
            <tr>
                <th>Num</th>
                <th>Nom Tâche</th>
                <th>Dernier État</th>
                <th>Dernier Partenaire</th>
                <th>État Paiement</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($taches): ?>
            <?php foreach ($taches as $tache): ?>
                <tr>
                    <td><?= $tache['num_tache'] ?></td>
                    <td><?= htmlspecialchars($tache['nom_tache']) ?></td>
                    <td><?= htmlspecialchars($tache['dernier_etat'] ?? 'non accompli') ?></td>
                    <td><?= htmlspecialchars($tache['dernier_partenaire'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($tache['etat_payement'] ?? '-') ?></td>
                    <td>
                        <a href="modifier_tache.php?id=<?= $tache['id'] ?>" class="btn btn-warning btn-sm">Modifier</a>
                        <a href="supprimer_tache.php?id=<?= $tache['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette tâche ?')">Supprimer</a>
                        <a href="details_tache.php?id=<?= $tache['id'] ?>" class="btn btn-info btn-sm">Détails</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">Aucune tâche trouvée</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#tasksTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
