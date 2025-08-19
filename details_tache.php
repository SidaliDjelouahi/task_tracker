<?php
session_start();
require_once 'connexion.php';
include 'sidebar.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user']['username'])) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['user']['id']; // id du user connecté

// Vérifier si l'id de tâche est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de tâche invalide");
}

$id_tache = (int)$_GET['id'];
$error = '';
$success = '';

// Récupérer les informations principales de la tâche
$stmt = $pdo->prepare("SELECT num_tache, nom_tache, montant_tache, versement FROM taches WHERE id = :id");
$stmt->execute([':id' => $id_tache]);
$tache = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tache) {
    die("Tâche introuvable");
}

// Traitement du formulaire ajout de détail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_detail'])) {
    $date_detail = $_POST['date_detail'] ?? date('Y-m-d H:i:s');
    $etat = $_POST['etat'] ?? 'non accompli';
    $detail_text = trim($_POST['detail_text'] ?? '');

    if ($detail_text === '') {
        $error = "Le champ détail est obligatoire.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO detail_tache (id_tache, id_partenaire, date, etat, detail_tache) 
                VALUES (:id_tache, :id_partenaire, :date, :etat, :detail)
            ");
            $stmt->execute([
                ':id_tache' => $id_tache,
                ':id_partenaire' => $id_user,
                ':date' => $date_detail,
                ':etat' => $etat,
                ':detail' => $detail_text
            ]);
            $success = "Détail ajouté avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout du détail : " . $e->getMessage();
        }
    }
}

// Récupérer tous les détails de la tâche
$stmt = $pdo->prepare("
    SELECT dt.id, dt.date, dt.etat, dt.detail_tache
    FROM detail_tache dt
    WHERE dt.id_tache = :id
    ORDER BY dt.date DESC
");
$stmt->execute([':id' => $id_tache]);
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails Tâche #<?= $tache['num_tache'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- important pour mobile -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">

    <h1 class="mb-4">Détails de la Tâche #<?= $tache['num_tache'] ?> - <?= htmlspecialchars($tache['nom_tache']) ?></h1>
    <p><strong>Montant :</strong> <?= htmlspecialchars($tache['montant_tache']) ?></p>
    <p><strong>Versement :</strong> <?= htmlspecialchars($tache['versement'] ?? 0) ?></p>

    <a href="liste_taches.php" class="btn btn-secondary mb-3">← Retour à la liste</a>
    <a href="ajouter_detail.php?id=<?= $id_tache ?>" class="btn btn-success mb-3">➕ Ajouter un détail</a>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Vue Desktop -->
    <div class="table-responsive d-none d-md-block">
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>État</th>
                    <th>Détail</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($details): ?>
                <?php foreach ($details as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['date']) ?></td>
                        <td><?= htmlspecialchars($d['etat']) ?></td>
                        <td><?= nl2br(htmlspecialchars($d['detail_tache'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">Aucun détail pour cette tâche</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Vue Mobile -->
    <div class="d-md-none">
        <?php if ($details): ?>
            <?php foreach ($details as $d): ?>
                <div class="card mb-2 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($d['date']) ?></h6>
                        <p class="mb-1"><strong>État :</strong> <?= htmlspecialchars($d['etat']) ?></p>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($d['detail_tache'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">Aucun détail pour cette tâche</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
