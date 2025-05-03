<?php
session_start();

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Rediriger vers la page d'accueil si déjà connecté
    header('Location: accueil.php');
    exit();
}

// Variable pour stocker les messages d'erreur
$error_message = "";

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $email = htmlspecialchars(trim($_POST['email']));
    $password = trim($_POST['password']);
    
    // Vérifications
    if (empty($email) || empty($password)) {
        $error_message = "Tous les champs sont obligatoires !";
    } else {
        // Connexion à la base de données
        $conn = new mysqli('localhost', 'root', '', 'PGE');
        
        if ($conn->connect_error) {
            $error_message = "Erreur de connexion : " . $conn->connect_error;
        } else {
            // Vérifier si l'email existe
            $stmt = $conn->prepare("SELECT id, password, nom FROM utilisateurs WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                // Récupérer les données de l'utilisateur
                $stmt->bind_result($id, $hashed_password, $nom);
                $stmt->fetch();
                
                // Vérifier le mot de passe
                if (password_verify($password, $hashed_password)) {
                    // Connexion réussie, configurer la session
                    $_SESSION['user_id'] = $id;
                    $_SESSION['email'] = $email;
                    $_SESSION['nom'] = $nom;
                    $_SESSION['logged_in'] = true; // Important: définir cette variable à true
                    
                    // Rediriger vers la page d'accueil
                    header('Location: accueil.php');
                    exit();
                } else {
                    $error_message = "Mot de passe incorrect.";
                }
            } else {
                $error_message = "Aucun utilisateur trouvé avec cet email.";
            }
            
            // Fermer la connexion
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - EventBladi</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            background-color: #f4f6f7;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .error-message {
            color: #ff0000;
            background-color: #ffe6e6;
            border: 1px solid #ff0000;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }

        /* Header Styles */
        .header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .logo {
            color: #3498db;
            font-size: 1.8rem;
            font-weight: bold;
        }

        .nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav a:hover {
            color: #3498db;
        }

        /* Form Styles */
        .form-container {
            max-width: 500px;
            margin: 120px auto 60px;
            padding: 20px;
        }

        .form-box {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .form-title {
            color: #3498db;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: #3498db;
            outline: none;
        }

        .btn-primary {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .redirect-link {
            text-align: center;
            margin-top: 20px;
        }

        .redirect-link a {
            color: #3498db;
            text-decoration: none;
        }

        .redirect-link a:hover {
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 50px 0 20px;
        }

        .footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .footer-grid > div {
            display: flex;
            flex-direction: column;
        }

        .footer-grid h4 {
            color: #3498db;
            font-size: 1.2rem;
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-grid h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #3498db;
        }

        .footer-grid p {
            margin-bottom: 10px;
            line-height: 1.6;
            color: #bdc3c7;
        }

        .footer-grid a {
            color: #ecf0f1;
            text-decoration: none;
            margin-bottom: 8px;
            transition: color 0.3s ease;
        }

        .footer-grid a:hover {
            color: #3498db;
        }

        .footer .copyright {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #95a5a6;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">EventBladi</h1>
            <nav class="nav">
                <a href="accueil.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="inscription.html"><i class="fas fa-user-plus"></i> S'inscrire</a>
            </nav>
        </div>
    </header>

    <!-- Formulaire de Connexion -->
    <div class="form-container">
        <form action="connexion.php" method="POST" class="form-box">
            <h2 class="form-title">Connexion</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email :</label>
                <input type="email" id="email" name="email" placeholder="Entrez votre email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mot de passe :</label>
                <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
            </div>
            <button type="submit" class="btn-primary">Se connecter</button>
            <p class="redirect-link">Vous n'avez pas de compte ? <a href="inscription.html">Inscrivez-vous ici.</a></p>
        </form>
    </div>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>EventBladi</h4>
                    <p>Plateforme de gestion d'événements moderne et intuitive.</p>
                </div>
                <div>
                    <h4>Liens Rapides</h4>
                    <a href="accueil.php">Accueil</a>
                    <a href="evenements.php">Événements</a>
                    <a href="contact.php">Contact</a>
                </div>
                <div>
                    <h4>Contactez-nous</h4>
                    <p>Email: support@eventbladi.com</p>
                    <p>Téléphone: +212 6 00 00 01 02</p>
                </div>
            </div>
            <div style="text-align:center; margin-top:30px;">
                &copy; 2025 EventBladi. Tous droits réservés.
            </div>
        </div>
    </footer>
</body>
</html>