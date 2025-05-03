<?php
// Configuration de la base de données
$host = 'localhost';
$dbname = 'pge';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Préparer la requête d'insertion
    $stmt = $pdo->prepare("INSERT INTO photos_salles (salle_id, chemin_photo) VALUES (?, ?)");

    // Liste des images pour chaque salle (assurez-vous que ces chemins correspondent à vos fichiers réels)
    $images = [
        1 => [
            'images/salles/salle1_1.png',
            'images/salles/salle1_2.png',
            'images/salles/salle1_3.png',
            'images/salles/salle1_4.png'
        ],
        2 => [
            'images/salles/salle2_1.png',
            'images/salles/salle2_2.png',
            'images/salles/salle2_3.png',
            'images/salles/salle2_4.png'
        ],
        3 => [
            'images/salles/salle3_1.jpg',
            'images/salles/salle3_2.jpg',
            'images/salles/salle3_3.jpg',
            'images/salles/salle3_4.jpg'
        ],
        4 => [
            'images/salles/salle4_1.jpg',
            'images/salles/salle4_2.jpg',
            'images/salles/salle4_3.jpg',
            'images/salles/salle4_4.jpg'
        ]
    ];

    // Insérer les images pour chaque salle
    foreach ($images as $salle_id => $salle_images) {
        foreach ($salle_images as $image_path) {
            $stmt->execute([$salle_id, $image_path]);
        }
    }

    echo "Images ajoutées avec succès !";

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>