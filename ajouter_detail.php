<?php
session_start();
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user']['username'])) {
    header("Location: login.php");
    exit();
}

// Vérifier si l'id de tâche est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de tâche invalide");
}

$id_tache = (int)$_GET['id'];
$error = '';
$success = '';

// Récupérer la tâche pour afficher le nom
$stmt = $pdo->prepare("SELECT num_tache, nom_tache FROM taches WHERE id = :id");
$stmt->execute([':id' => $id_tache]);
$tache = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tache) die("Tâche introuvable");

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_detail = $_POST['date_detail'] ?? date('Y-m-d H:i:s');
    $etat = $_POST['etat'] ?? 'non accompli';
    $detail_text = trim($_POST['detail_text'] ?? '');

    if ($detail_text === '') {
        $error = "Le champ détail est obligatoire.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO detail_tache (id_tache, date, etat, detail_tache)
                VALUES (:id_tache, :date, :etat, :detail)
            ");
            $stmt->execute([
                ':id_tache' => $id_tache,
                ':date' => $date_detail,
                ':etat' => $etat,
                ':detail' => $detail_text
            ]);

            $success = "Détail ajouté avec succès.";
            // Redirection automatique après 2 secondes
            echo "<meta http-equiv='refresh' content='2;URL=details_tache.php?id=$id_tache'>";

        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout du détail : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter détail Tâche #<?= $tache['num_tache'] ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h1 class="mb-4">Ajouter un détail pour la Tâche #<?= $tache['num_tache'] ?> - <?= htmlspecialchars($tache['nom_tache']) ?></h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="datetime-local" name="date_detail" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">État</label>
            <select name="etat" class="form-select">
                <option value="non accompli">Non accompli</option>
                <option value="en cours">En cours</option>
                <option value="accompli">Accompli</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Détail</label>
            <input type="text" name="detail_text" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="details_tache.php?id=<?= $id_tache ?>" class="btn btn-secondary">Annuler</a>
    </form>
</div>
</body>
</html>
