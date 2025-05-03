<?php
session_start();

if (!isset($_SESSION['reservation_data'])) {
    header("Location: reservation.php");
    exit();
}

$reservation_data = $_SESSION['reservation_data'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif de Réservation - EventBladi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-6 text-blue-600">Récapitulatif de Réservation</h1>
        <form action="finaliser_reservation.php" method="POST" class="max-w-lg mx-auto bg-white shadow-lg rounded-lg p-6 space-y-4">
            <input type="hidden" name="confirm_reservation" value="1">
            <div>
                <label class="block text-gray-700 font-bold">Événement :</label>
                <p class="text-gray-600"><?= htmlspecialchars($reservation_data['titre_evenement']); ?></p>
            </div>
            <div>
                <label class="block text-gray-700 font-bold">Nombre de Places :</label>
                <p class="text-gray-600"><?= htmlspecialchars($reservation_data['places_reservees']); ?></p>
            </div>
            <div>
                <label class="block text-gray-700 font-bold">Méthode de Paiement :</label>
                <p class="text-gray-600"><?= htmlspecialchars($reservation_data['methode_paiement']); ?></p>
            </div>
            <div>
                <label class="block text-gray-700 font-bold">Prix Total :</label>
                <p class="text-gray-600"><?= htmlspecialchars($reservation_data['prix_total']); ?> MAD</p>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-300">
                Confirmer et Enregistrer
            </button>
        </form>
    </div>
</body>
</html>