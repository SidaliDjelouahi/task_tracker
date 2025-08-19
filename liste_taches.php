<?php
session_start();
require_once 'connexion.php';
include 'sidebar.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user']['username'])) {
    header("Location: login.php");
    exit();
}

// Récupération des tâches (sans filtre côté serveur)
$sql = "
    SELECT t.id, t.num_tache, t.nom_tache, t.payement_client,
           dt.etat AS dernier_etat,
           GROUP_CONCAT(u.username ORDER BY u.username SEPARATOR ', ') AS tous_partenaires
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
    LEFT JOIN partenaires_tache pt ON t.id = pt.id_tache
    LEFT JOIN users u ON pt.id_partenaire = u.id
    GROUP BY t.id
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
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- important pour mobile -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="p-3">

<div class="container-fluid">

    
    <!-- Message flash de succès -->
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success text-center fw-bold fs-5">
          <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>


    <h1 class="mb-4 text-center">Liste des tâches</h1>

    <!-- Ligne avec bouton + recherche -->
    <div class="row mb-3 g-2 align-items-center">
        <div class="col-12 col-md-auto">
            <a href="ajouter_tache.php" class="btn btn-primary w-100">
                <i class="bi bi-plus"></i> Ajouter
            </a>
        </div>
        <div class="col-12 col-md">
            <input type="text" id="searchInput" class="form-control" placeholder="Tapez pour filtrer toutes les colonnes...">
        </div>
    </div>

    <!-- Vue Desktop -->
    <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered table-striped align-middle" id="tasksTable">
            <thead class="table-light">
                <tr>
                    <th>Num</th>
                    <th>Nom Tâche</th>
                    <th>Dernier État</th>
                    <th>Partenaires</th>
                    <th>Paiement</th>
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
                        <td><?= htmlspecialchars($tache['tous_partenaires'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($tache['payement_client'] ?? '-') ?></td>
                        <td class="text-nowrap">
                            <a href="modifier_tache.php?id=<?= $tache['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                            <a href="supprimer_tache.php?id=<?= $tache['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette tâche ?')"><i class="bi bi-trash"></i></a>
                            <a href="details_tache.php?id=<?= $tache['id'] ?>" class="btn btn-info btn-sm"><i class="bi bi-eye"></i></a>
                            <?php if ($_SESSION['user']['rank'] === 'admin'): ?>
                                <a href="modifier_pourcentage_partenaire.php?id=<?= $tache['id'] ?>" class="btn btn-secondary btn-sm"><i class="bi bi-percent"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">Aucune tâche trouvée</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Vue Mobile -->
    <div class="d-md-none" id="tasksCards">
        <?php if ($taches): ?>
            <?php foreach ($taches as $tache): ?>
                <div class="card mb-2 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-1">#<?= $tache['num_tache'] ?> - <?= htmlspecialchars($tache['nom_tache']) ?></h5>
                        <p class="mb-1"><strong>État :</strong> <?= htmlspecialchars($tache['dernier_etat'] ?? 'non accompli') ?></p>
                        <p class="mb-1"><strong>Partenaires :</strong> <?= htmlspecialchars($tache['tous_partenaires'] ?? '-') ?></p>
                        <p class="mb-2"><strong>Paiement :</strong> <?= htmlspecialchars($tache['payement_client'] ?? '-') ?></p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="modifier_tache.php?id=<?= $tache['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                            <a href="supprimer_tache.php?id=<?= $tache['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette tâche ?')"><i class="bi bi-trash"></i></a>
                            <a href="details_tache.php?id=<?= $tache['id'] ?>" class="btn btn-info btn-sm"><i class="bi bi-eye"></i></a>
                            <?php if ($_SESSION['user']['rank'] === 'admin'): ?>
                                <a href="modifier_pourcentage_partenaire.php?id=<?= $tache['id'] ?>" class="btn btn-secondary btn-sm"><i class="bi bi-percent"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">Aucune tâche trouvée</p>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Filtrage pour tableau + cartes
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();

    // Mode desktop
    document.querySelectorAll('#tasksTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });

    // Mode mobile
    document.querySelectorAll('#tasksCards .card').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
