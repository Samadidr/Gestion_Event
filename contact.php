<?php
// Démarrer la session
session_start();

// Variables pour stocker les messages
$success_message = "";
$error_message = "";

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nom = htmlspecialchars(trim($_POST['nom']));
    $email = htmlspecialchars(trim($_POST['email']));
    $sujet = htmlspecialchars(trim($_POST['sujet']));
    $message = htmlspecialchars(trim($_POST['message']));

    // Vérifications
    if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
        $error_message = "Tous les champs sont obligatoires !";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "L'adresse email n'est pas valide.";
    } else {
        // Connexion à la base de données
        $conn = new mysqli('localhost', 'root', '', 'PGE');
        
        if ($conn->connect_error) {
            $error_message = "Erreur de connexion à la base de données : " . $conn->connect_error;
        } else {
            // Préparer et exécuter la requête pour insérer le message
            $stmt = $conn->prepare("INSERT INTO contacts (nom, email, sujet, message, date_envoi) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $nom, $email, $sujet, $message);
            
            if ($stmt->execute()) {
                $success_message = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
                
                // Réinitialiser les champs du formulaire
                $nom = $email = $sujet = $message = "";
                
                // Option: Envoyer un email de notification
                // mail('admin@eventbladi.fr', 'Nouveau message de contact', "Nom: $nom\nEmail: $email\nSujet: $sujet\nMessage: $message");
            } else {
                $error_message = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.";
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
    <title>Contact - EventBladi</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Styles spécifiques à la page contact */
        .contact-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .contact-info {
            margin-bottom: 30px;
        }
        
        .contact-form {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-group textarea {
            min-height: 150px;
        }
        
        .success-message {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        
        .btn-submit {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: #0069d9;
        }
        
        .contact-details {
            display: flex;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .contact-item {
            flex: 1;
            min-width: 250px;
            margin: 10px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        
        .contact-item i {
            font-size: 24px;
            color: #007bff;
            margin-bottom: 10px;
        }
        h1{
            color: #007bff;
        }

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
            color:rgb(43, 81, 121);
            font-size: 1.8rem;
            font-weight: bold;
            margin-left:30px;
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
    <!-- En-tête -->
    <header class="header">
        <div class="container">
            <h1 class="logo">EventBladi</h1>
        </div>
    </header><br><br>
    
    <!-- Contenu principal -->
    <main>
        <div class="contact-container">
            <h1>Contactez-nous</h1>
            
            <div class="contact-info">
                <p>Vous avez des questions ou des suggestions ? N'hésitez pas à nous contacter en utilisant le formulaire ci-dessous ou via nos coordonnées de contact.</p>
            </div>
            
            <!-- Messages de succès ou d'erreur -->
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire de contact -->
            <div class="contact-form">
                <form action="contact.php" method="POST">
                    <div class="form-group">
                        <label for="nom">Nom complet :</label>
                        <input type="text" id="nom" name="nom" placeholder="Votre nom" value="<?php echo isset($nom) ? htmlspecialchars($nom) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email :</label>
                        <input type="email" id="email" name="email" placeholder="Votre adresse email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sujet">Sujet :</label>
                        <input type="text" id="sujet" name="sujet" placeholder="Le sujet de votre message" value="<?php echo isset($sujet) ? htmlspecialchars($sujet) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message :</label>
                        <textarea id="message" name="message" placeholder="Votre message" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">Envoyer le message</button>
                </form>
            </div>
            
            <!-- Informations de contact -->
            <div class="contact-details">
                <div class="contact-item">
                    <i class="fa fa-envelope"></i>
                    <h3>Email</h3>
                    <p>support@eventbladi.com</p>
                </div>
                
                <div class="contact-item">
                    <i class="fa fa-phone"></i>
                    <h3>Téléphone</h3>
                    <p>+212 6 00 00 01 02</p>
                </div>
                
                <div class="contact-item">
                    <i class="fa fa-map-marker"></i>
                    <h3>Adresse</h3>
                    <p>Salé hay karima<br>Rue Qayrawan N1001, Maroc</p>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Pied de page -->
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
    
    <!-- Vous pourriez vouloir inclure Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</body>
</html>