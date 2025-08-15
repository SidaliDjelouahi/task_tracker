<?php
session_start();
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user']['username'])) {
    header("Location: login.php");
    exit();
}

// Vérifier si l'id est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de tâche invalide");
}

$id_tache = (int)$_GET['id'];
$error = '';
$success = '';

// Récupérer la tâche existante
$sql = "
    SELECT t.id, t.num_tache, t.nom_tache, t.montant_tache,
           dt.etat AS dernier_etat,
           pt.etat_payement, pt.id_partenaire
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
        SELECT pt1.id_tache, pt1.id_partenaire, pt1.etat_payement, pt1.id AS pt_id
        FROM partenaires_tache pt1
        INNER JOIN (
            SELECT id_tache, MAX(id) AS last_id
            FROM partenaires_tache
            GROUP BY id_tache
        ) pt2 ON pt1.id_tache = pt2.id_tache AND pt1.id = pt2.last_id
    ) pt ON t.id = pt.id_tache
    WHERE t.id = :id
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id_tache]);
$tache = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tache) {
    die("Tâche introuvable");
}

// Récupérer tous les partenaires pour le select
$users = $pdo->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_tache = trim($_POST['nom_tache'] ?? '');
    $montant_tache = $_POST['montant_tache'] ?? 0;
    $partenaire_id = $_POST['partenaire_id'] ?? null;
    $etat = $_POST['etat'] ?? 'non accompli';
    $etat_payement = $_POST['etat_payement'] ?? '';

    if ($nom_tache === '' || empty($partenaire_id)) {
        $error = "Nom de tâche et partenaire obligatoires.";
    } else {
        try {
            $pdo->beginTransaction();

            // Mettre à jour taches
            $sqlUpdateTache = "UPDATE taches SET nom_tache = :nom, montant_tache = :montant WHERE id = :id";
            $stmt1 = $pdo->prepare($sqlUpdateTache);
            $stmt1->execute([
                ':nom' => $nom_tache,
                ':montant' => $montant_tache,
                ':id' => $id_tache
            ]);

            // Ajouter un nouveau detail_tache pour enregistrer l'état
            $sqlDetail = "INSERT INTO detail_tache (id_tache, etat, date) VALUES (:id_tache, :etat, NOW())";
            $stmt2 = $pdo->prepare($sqlDetail);
            $stmt2->execute([
                ':id_tache' => $id_tache,
                ':etat' => $etat
            ]);

            // Mettre à jour ou insérer partenaires_tache (dernier partenaire)
            $sqlPartenaire = "INSERT INTO partenaires_tache (id_tache, id_partenaire, etat_payement) VALUES (:id_tache, :id_partenaire, :etat_payement)";
            $stmt3 = $pdo->prepare($sqlPartenaire);
            $stmt3->execute([
                ':id_tache' => $id_tache,
                ':id_partenaire' => $partenaire_id,
                ':etat_payement' => $etat_payement
            ]);

            $pdo->commit();
            $success = "Tâche modifiée avec succès.";

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Erreur lors de la modification : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Tâche</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h1 class="mb-4">Modifier Tâche</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Numéro de tâche</label>
            <input class="form-control" value="<?= $tache['num_tache'] ?>" readonly disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Nom de la tâche</label>
            <input class="form-control" name="nom_tache" value="<?= htmlspecialchars($tache['nom_tache']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Montant</label>
            <input type="number" step="0.01" name="montant_tache" class="form-control" value="<?= $tache['montant_tache'] ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Dernier État</label>
            <select name="etat" class="form-select">
                <?php
                $etats = ['non accompli', 'en cours', 'accompli'];
                foreach ($etats as $e) {
                    $sel = ($tache['dernier_etat'] ?? 'non accompli') === $e ? 'selected' : '';
                    echo "<option value='$e' $sel>$e</option>";
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Dernier Partenaire</label>
            <select name="partenaire_id" class="form-select" required>
                <?php foreach ($users as $u): 
                    $sel = $tache['id_partenaire'] == $u['id'] ? 'selected' : '';
                ?>
                    <option value="<?= $u['id'] ?>" <?= $sel ?>><?= htmlspecialchars($u['username']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">État Paiement</label>
            <input class="form-control" name="etat_payement" value="<?= htmlspecialchars($tache['etat_payement'] ?? '') ?>">
        </div>
        <button class="btn btn-success">Enregistrer les modifications</button>
        <a href="liste_taches.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>
</body>
</html>
