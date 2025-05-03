<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    // Vérification des mots de passe
    if ($password !== $confirm_password) {
        echo "Les mots de passe ne correspondent pas.";
        exit;
    }

    // Connexion à la base de données
    $conn = new mysqli('localhost', 'root', '', 'nom_de_ta_base');

    if ($conn->connect_error) {
        die("Connexion échouée : " . $conn->connect_error);
    }

    // Insertion dans la base de données
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO utilisateurs (nom, email, password) VALUES ('$nom', '$email', '$password_hashed')";

    if ($conn->query($sql) === TRUE) {
        echo "Inscription réussie !";
    } else {
        echo "Erreur : " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
