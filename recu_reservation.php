<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

// Vérifier si l'ID de réservation est présent
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['reservation_message'] = "Identifiant de réservation invalide.";
    $_SESSION['reservation_message_type'] = "error";
    header("Location: mes_reservations.php");
    exit();
}

$reservation_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Connexion à la base de données
$host = 'localhost';
$dbname = 'PGE';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données.");
}

// Récupérer les détails de la réservation
try {
    $stmt = $pdo->prepare("
        SELECT r.*, e.titre, e.date_event, e.heure_debut, s.nom as salle_nom
        FROM reservations r
        JOIN evenements e ON r.evenement_id = e.id
        JOIN salles s ON e.salle_id = s.id
        WHERE r.id = ? AND r.utilisateur_id = ?
    ");
    $stmt->execute([$reservation_id, $user_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        $_SESSION['reservation_message'] = "Réservation non trouvée ou accès non autorisé.";
        $_SESSION['reservation_message_type'] = "error";
        header("Location: mes_reservations.php");
        exit();
    }

    // Récupérer les informations de l'utilisateur
    $stmt = $pdo->prepare("SELECT nom, email FROM utilisateurs WHERE id = ?");
    $stmt->execute([$user_id]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['reservation_message'] = "Erreur lors de la récupération des données: " . $e->getMessage();
    $_SESSION['reservation_message_type'] = "error";
    header("Location: mes_reservations.php");
    exit();
}

// Formater la date pour l'affichage
$date_event = new DateTime($reservation['date_event']);
$date_formatted = $date_event->format('d/m/Y');

// Formater l'heure pour l'affichage
$heure_debut = new DateTime($reservation['heure_debut']);
$heure_formatted = $heure_debut->format('H:i');

// Générer un numéro de référence unique
$reference = 'EB-' . str_pad($reservation_id, 6, '0', STR_PAD_LEFT);

// Marquer ce reçu comme téléchargé s'il a été téléchargé
if (isset($_GET['downloaded']) && $_GET['downloaded'] == 'true') {
    // Mettre à jour la base de données pour marquer ce reçu comme téléchargé
    try {
        $stmt = $pdo->prepare("
            UPDATE reservations 
            SET recu_telecharge = 1 
            WHERE id = ? AND utilisateur_id = ?
        ");
        $stmt->execute([$reservation_id, $user_id]);
    } catch (Exception $e) {
        // Continuer même en cas d'erreur
        error_log("Erreur lors de la mise à jour du statut de téléchargement: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Réservation #<?= $reservation_id ?> - EventBladi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                font-size: 12pt;
            }
            .print-container {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 print-container">
        <div class="flex justify-between items-center mb-6 no-print">
            <h1 class="text-3xl font-bold text-blue-600">Reçu de Réservation</h1>
            <div>
                <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded mr-2 hover:bg-blue-700">
                    Imprimer
                </button>
                <button onclick="generatePDF()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Télécharger PDF
                </button>
            </div>
        </div>

        <!-- Messages de succès -->
        <?php if (isset($_GET['downloaded']) && $_GET['downloaded'] == 'true'): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 no-print" role="alert">
            <strong class="font-bold">Succès!</strong>
            <span class="block sm:inline">Votre reçu a été téléchargé et sauvegardé dans votre compte.</span>
        </div>
        <?php endif; ?>

        <div class="max-w-2xl mx-auto bg-white shadow-lg rounded-lg p-8 mb-8" id="receipt">
            <!-- En-tête -->
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-blue-600">EventBladi</h2>
                    <p class="text-gray-600">Votre plateforme d'événements au Maroc</p>
                </div>
                <div class="text-right">
                    <p class="font-bold">REÇU #<?= $reference ?></p>
                    <p class="text-gray-600">Date: <?= date('d/m/Y') ?></p>
                </div>
            </div>
            
            <!-- Infos Client -->
            <div class="mb-6">
                <h3 class="font-bold text-lg mb-2">Informations Client</h3>
                <p><span class="font-semibold">Nom:</span> <?= htmlspecialchars($utilisateur['nom']) ?> </p>
                <p><span class="font-semibold">Email:</span> <?= htmlspecialchars($utilisateur['email']) ?></p>
            </div>
            
            <!-- Détails Réservation -->
            <div class="mb-6">
                <h3 class="font-bold text-lg mb-2">Détails de la Réservation</h3>
                <p><span class="font-semibold">Événement:</span> <?= htmlspecialchars($reservation['titre']) ?></p>
                <p><span class="font-semibold">Lieu:</span> <?= htmlspecialchars($reservation['salle_nom']) ?></p>
                <p><span class="font-semibold">Date:</span> <?= $date_formatted ?></p>
                <p><span class="font-semibold">Heure:</span> <?= $heure_formatted ?></p>
            </div>
            
            <!-- Détails Paiement -->
            <div class="mb-6">
                <h3 class="font-bold text-lg mb-2">Détails du Paiement</h3>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-2 px-4 text-left">Description</th>
                            <th class="py-2 px-4 text-center">Quantité</th>
                            <th class="py-2 px-4 text-right">Prix unitaire</th>
                            <th class="py-2 px-4 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t">
                            <td class="py-2 px-4">Places pour <?= htmlspecialchars($reservation['titre']) ?></td>
                            <td class="py-2 px-4 text-center"><?= $reservation['places_reservees'] ?></td>
                            <td class="py-2 px-4 text-right"><?= number_format($reservation['montant_total'] / $reservation['places_reservees'], 2) ?> MAD</td>
                            <td class="py-2 px-4 text-right"><?= number_format($reservation['montant_total'], 2) ?> MAD</td>
                        </tr>
                        <tr class="border-t">
                            <td colspan="3" class="py-2 px-4 text-right font-bold">Total</td>
                            <td class="py-2 px-4 text-right font-bold"><?= number_format($reservation['montant_total'], 2) ?> MAD</td>
                        </tr>
                    </tbody>
                </table>
                <p class="mt-2"><span class="font-semibold">Méthode de paiement:</span> <?= htmlspecialchars($reservation['methode_paiement']) ?></p>
                <p class="mt-2"><span class="font-semibold">Date de paiement:</span> <?= (new DateTime($reservation['date_reservation']))->format('d/m/Y H:i') ?></p>
            </div>
            
            <!-- Notes -->
            <div class="mt-8 text-center text-gray-600 text-sm">
                <p>Ce reçu est la preuve de votre réservation. Veuillez le présenter lors de l'événement.</p>
                <p>Pour toute question, contactez-nous: support@eventbladi.ma</p>
                <p class="mt-4">Merci d'avoir choisi EventBladi!</p>
            </div>
            
            
        
        <div class="text-center no-print">
            <a href="mes_reservations.php" class="text-blue-600 hover:underline">Retour à mes réservations</a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function generatePDF() {
            const element = document.getElementById('receipt');
            const opt = {
                margin: 10,
                filename: 'reservation-<?= $reference ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Générer le PDF
            html2pdf().set(opt).from(element).save().then(() => {
                // Après le téléchargement, rediriger pour marquer comme téléchargé
                window.location.href = 'recu_reservation.php?id=<?= $reservation_id ?>&downloaded=true';
            });
        }
    </script>
</body>
</html>