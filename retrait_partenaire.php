<?php
session_start();
require_once 'connexion.php';
include 'sidebar.php';

if (!isset($_SESSION['user']['username'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID partenaire invalide.");
}

$id_partenaire = (int)$_GET['id'];
$error = '';
$success = '';

// Suppression d'un retrait si demandé
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_retrait = (int)$_GET['delete'];
    $stmt_del = $pdo->prepare("DELETE FROM retrait WHERE id = ? AND id_partenaire = ?");
    if ($stmt_del->execute([$id_retrait, $id_partenaire])) {
        $success = "Retrait supprimé avec succès.";
    } else {
        $error = "Erreur lors de la suppression du retrait.";
    }
}

// Récupérer le nom du partenaire
$stmt_user = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt_user->execute([$id_partenaire]);
$partenaire = $stmt_user->fetch(PDO::FETCH_ASSOC);
if (!$partenaire) die("Partenaire introuvable.");

// Calcul du total des versements des tâches accomplies pour ce partenaire
$sql_total = "
    SELECT COALESCE(SUM(t.versement * pt.pourcentage_partenaire / 100),0) AS total_versements
    FROM taches t
    INNER JOIN detail_tache dt ON dt.id_tache = t.id
    INNER JOIN partenaires_tache pt ON pt.id_tache = t.id
    WHERE dt.etat = 'Accompli'
      AND pt.id_partenaire = ?
";
$stmt_total = $pdo->prepare($sql_total);
$stmt_total->execute([$id_partenaire]);
$total_versements = (float)$stmt_total->fetchColumn();

// Total des retraits déjà effectués
$stmt_retraits = $pdo->prepare("SELECT COALESCE(SUM(montant_retrait),0) FROM retrait WHERE id_partenaire = ?");
$stmt_retraits->execute([$id_partenaire]);
$total_retraits = (float)$stmt_retraits->fetchColumn();

// Montant disponible réel
$total_caisse = $total_versements - $total_retraits;

// Traitement du formulaire de retrait
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $montant_retrait = floatval($_POST['montant_retrait'] ?? 0);

    if ($montant_retrait <= 0) {
        $error = "Veuillez entrer un montant supérieur à 0.";
    } elseif ($montant_retrait > $total_caisse) {
        $error = "Le montant du retrait ne peut pas dépasser la caisse disponible (".number_format($total_caisse,2,","," ")." DA).";
    } else {
        $stmt_insert = $pdo->prepare("INSERT INTO retrait (id_partenaire, montant_retrait, datetime) VALUES (:id_partenaire, :montant, NOW())");
        $stmt_insert->execute([
            ':id_partenaire' => $id_partenaire,
            ':montant' => $montant_retrait
        ]);
        $success = "Retrait de ".number_format($montant_retrait,2,","," ")." DA effectué avec succès.";
        // Mise à jour du total_caisse immédiatement
        $total_caisse -= $montant_retrait;
    }
}

// Récupérer l'historique des retraits
$stmt_hist = $pdo->prepare("SELECT id, montant_retrait, datetime FROM retrait WHERE id_partenaire = ? ORDER BY datetime DESC");
$stmt_hist->execute([$id_partenaire]);
$retraits = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Retrait Partenaire</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h2>Retrait pour le partenaire : <strong><?= htmlspecialchars($partenaire['username']) ?></strong></h2>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <strong>Montant disponible en caisse :</strong> <?= number_format($total_caisse,2,","," ") ?> DA
    </div>

    <form method="post" class="mb-4">
        <div class="mb-3">
            <label class="form-label">Montant à retirer</label>
            <input type="number" step="0.01" name="montant_retrait" max="<?= $total_caisse ?>" class="form-control" required>
            <div class="form-text">Montant maximal : <?= number_format($total_caisse,2,","," ") ?> DA</div>
        </div>
        <button class="btn btn-warning">Effectuer le retrait</button>
        <a href="caisse_partenaire.php" class="btn btn-secondary">← Retour</a>
    </form>

    <h3>Historique des retraits</h3>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Date / Heure</th>
                <th>Montant Retiré</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if($retraits): ?>
                <?php foreach($retraits as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['datetime']) ?></td>
                        <td><?= number_format($r['montant_retrait'],2,","," ") ?> DA</td>
                        <td>
                            <a href="retrait_partenaire.php?id=<?= $id_partenaire ?>&delete=<?= $r['id'] ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Voulez-vous vraiment supprimer ce retrait ?');">
                               Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" class="text-center">Aucun retrait effectué</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
