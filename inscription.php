<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "1. Début du script";  // Débogage

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "2. Formulaire soumis";  // Débogage

    // Récupérer et nettoyer les données du formulaire
    $name = htmlspecialchars(trim($_POST['name'])); // Correspond au champ "name" du formulaire
    $email = htmlspecialchars(trim($_POST['email']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirmPassword']); // Correspond au champ "confirmPassword" du formulaire

    // Vérifications des champs obligatoires
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        die("Tous les champs sont obligatoires !");
    }

    // Vérifier si l'email est valide
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Adresse email invalide.");
    }

    // Vérifier si les mots de passe correspondent
    if ($password !== $confirm_password) {
        die("Les mots de passe ne correspondent pas.");
    }

    // Vérifier la longueur du mot de passe
    if (strlen($password) < 6) {
        die("Le mot de passe doit contenir au moins 6 caractères.");
    }

    // Connexion à la base de données
    $conn = new mysqli('localhost', 'root', '', 'PGE');

    if ($conn->connect_error) {
        die("Erreur de connexion : " . $conn->connect_error);
    }
    echo "3. Connexion à la base de données réussie";  // Débogage

    // Vérifier si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        die("Cet email est déjà utilisé.");
    }
    $stmt->close();

    // Hacher le mot de passe ici (avant de l'utiliser dans l'insertion)
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insérer les données dans la base
    $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password_hashed);

    if ($stmt->execute()) {
        echo "4. Inscription réussie";  // Débogage
        // Redirection vers la page de connexion après une inscription réussie
        header("Location: connexion.html");
        exit; // Il est important d'utiliser exit après header pour arrêter l'exécution du script
    } else {
        echo "Erreur d'insertion : " . $stmt->error;
    }

    // Fermer la connexion
    $stmt->close();
    $conn->close();
}
?>
