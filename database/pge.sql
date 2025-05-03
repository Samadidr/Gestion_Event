-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 03 mai 2025 à 23:44
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `pge`
--

-- --------------------------------------------------------

--
-- Structure de la table `administrateurs`
--

CREATE TABLE `administrateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `administrateurs`
--

INSERT INTO `administrateurs` (`id`, `nom`, `email`, `mot_de_passe`, `date_creation`) VALUES
(1, 'Administrateur', 'admin@eventbladi.com', '$2y$10$rVfcFaWecJ2VnbOOcreOyuWZpSgqNegBVBcolNDP3JzZfiObnC/gW', '2025-04-29 22:35:31');

-- --------------------------------------------------------

--
-- Structure de la table `evenements`
--

CREATE TABLE `evenements` (
  `id` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `date_event` datetime NOT NULL,
  `heure_debut` time DEFAULT NULL,
  `duree` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `places_disponibles` int(11) NOT NULL,
  `salle_id` int(11) NOT NULL,
  `prix_ticket` decimal(10,2) NOT NULL,
  `utilisateur_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `evenements`
--

INSERT INTO `evenements` (`id`, `titre`, `description`, `date_event`, `heure_debut`, `duree`, `created_at`, `places_disponibles`, `salle_id`, `prix_ticket`, `utilisateur_id`) VALUES
(1, 'ANNIVERSAIRE', 'anniv d\'adam', '2025-05-04 19:45:00', '19:45:00', 120, '2025-04-27 15:34:40', 38, 1, 20.00, 1),
(2, 'FORMATION', 'formation de diagnostique d\'automobile', '2025-05-10 17:00:00', '17:00:00', 180, '2025-04-27 15:40:28', 97, 2, 100.00, 1),
(3, 'Conférence', 'conférences d\'affaires', '2025-05-02 10:00:00', '10:00:00', 120, '2025-04-29 22:22:19', 100, 3, 0.00, 2),
(4, 'ANNIVERSAIRE', 'anniv d\'ali', '2025-05-02 19:20:00', '19:20:00', 120, '2025-04-30 11:15:34', 40, 1, 50.00, 2);

-- --------------------------------------------------------

--
-- Structure de la table `methode_paiement`
--

CREATE TABLE `methode_paiement` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `methode_paiement`
--

INSERT INTO `methode_paiement` (`id`, `nom`) VALUES
(2, 'carte'),
(1, 'espèces'),
(3, 'paypal');

-- --------------------------------------------------------

--
-- Structure de la table `photos_salles`
--

CREATE TABLE `photos_salles` (
  `id` int(11) NOT NULL,
  `salle_id` int(11) NOT NULL,
  `chemin_photo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `photos_salles`
--

INSERT INTO `photos_salles` (`id`, `salle_id`, `chemin_photo`) VALUES
(1, 1, 'images/salles/salle1_1.png'),
(2, 1, 'images/salles/salle1_2.png'),
(3, 1, 'images/salles/salle1_3.png'),
(4, 1, 'images/salles/salle1_4.png'),
(5, 2, 'images/salles/salle2_1.png'),
(6, 2, 'images/salles/salle2_2.png'),
(7, 2, 'images/salles/salle2_3.png'),
(8, 2, 'images/salles/salle2_4.png'),
(9, 3, 'images/salles/salle3_1.jpg'),
(10, 3, 'images/salles/salle3_2.jpg'),
(11, 3, 'images/salles/salle3_3.jpg'),
(12, 3, 'images/salles/salle3_4.jpg'),
(13, 4, 'images/salles/salle4_1.jpg'),
(14, 4, 'images/salles/salle4_2.jpg'),
(15, 4, 'images/salles/salle4_3.jpg'),
(16, 4, 'images/salles/salle4_4.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `evenement_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `places_reservees` int(11) NOT NULL,
  `date_reservation` datetime DEFAULT current_timestamp(),
  `methode_paiement` varchar(50) DEFAULT NULL,
  `montant_total` decimal(10,2) DEFAULT NULL,
  `recu_telecharge` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `evenement_id`, `utilisateur_id`, `places_reservees`, `date_reservation`, `methode_paiement`, `montant_total`, `recu_telecharge`) VALUES
(1, 1, 1, 2, '2025-04-27 15:34:52', 'espece', 40.00, 1),
(3, 2, 2, 3, '2025-04-29 22:27:06', 'espece', 300.00, 1);

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

CREATE TABLE `salles` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `capacite` int(11) NOT NULL,
  `localisation` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO `salles` (`id`, `nom`, `capacite`, `localisation`) VALUES
(1, 'Palais Andalou', 200, 'Palais Andalou Rabat'),
(2, 'Les Noces d’Or', 150, 'Rabat'),
(3, 'Salle Al Qods', 100, 'Rabat'),
(4, 'Espace Riviera', 300, 'Rabat'),
(5, 'Dome Émeraude', 250, 'Rabat'),
(6, 'Les Jardins de Rabat', 180, 'Rabat'),
(7, 'La Perle Blanche', 120, 'Rabat'),
(8, 'Salons Bahia', 220, 'Rabat'),
(9, 'Rabat Royal Events', 350, 'Rabat'),
(10, 'Espace Zénith', 130, 'Rabat'),
(11, 'Salle El Wiam', 100, 'Salé'),
(12, 'Nuit d’Étoiles', 180, 'Salé'),
(13, 'Palais El Boustane', 250, 'Salé'),
(14, 'Salle Le Diamant', 90, 'Salé'),
(15, 'Espace Al Baraka', 200, 'Salé'),
(16, 'Luxe Mariage', 220, 'Salé'),
(17, 'Salle Marjane', 150, 'Salé'),
(18, 'Al Farah Events', 170, 'Salé'),
(19, 'La Roseraie', 190, 'Salé'),
(20, 'Dar Al Bahja', 210, 'Salé'),
(21, 'Kénitra Palace', 300, 'Kénitra'),
(22, 'Espace Safir', 200, 'Kénitra'),
(23, 'Les Jardins de Kénitra', 250, 'Kénitra'),
(24, 'La Perle de Kénitra', 180, 'Kénitra'),
(25, 'Salle Al Ikram', 120, 'Kénitra'),
(26, 'Royal Event Kénitra', 280, 'Kénitra'),
(27, 'Dome de l’Atlas', 140, 'Kénitra'),
(28, 'Zénith Kénitra', 160, 'Kénitra'),
(29, 'Palais des Fleurs', 200, 'Kénitra'),
(30, 'Salle Lumière', 150, 'Kénitra'),
(31, 'Salle Al Firdaws', 100, 'Témara'),
(32, 'Espace Mariage Témara', 180, 'Témara'),
(33, 'Palais Layali', 220, 'Témara'),
(34, 'Les Nuits Blanches', 130, 'Témara'),
(35, 'La Fontaine Blanche', 160, 'Témara'),
(36, 'Dar Layina', 190, 'Témara'),
(37, 'Salle Prestige', 170, 'Témara'),
(38, 'Espace Rêverie', 200, 'Témara'),
(39, 'Témara Événementiel', 140, 'Témara');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_inscription` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `email`, `password`, `date_inscription`) VALUES
(1, 'ali madani', 'aaa@gmail.com', '$2y$10$.6w8aogrujiBKdlk08adjuvBSmrw.XM/1d0X9u5xX1.qV47HqZwYW', '2025-04-27 15:31:45'),
(2, 'Adil elamrani', 'bbb@gmail.com', '$2y$10$NmP6KZeH6/5KtaOwhb2Km.zpMilOmWc9J.FImdLh6c2BSgZCH.UWG', '2025-04-29 22:13:46'),
(3, 'adil malki', 'sss@gmail.com', '$2y$10$JtOIzh2UvjXSL.Bo0hCDu.bkxik2qMiGsQNhp1PaaZjQMWxykFN9G', '2025-04-30 11:08:54'),
(4, 'MOHAMED ALAOUI', 'VVV@gmail.com', '$2y$10$K6ZsSpk9Jcf0VRT8bzVTku5NWN3J2stRxD/5IkD6ZCP//eTB7z9Nu', '2025-04-30 11:11:48');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `administrateurs`
--
ALTER TABLE `administrateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `evenements`
--
ALTER TABLE `evenements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salle_id` (`salle_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `methode_paiement`
--
ALTER TABLE `methode_paiement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `photos_salles`
--
ALTER TABLE `photos_salles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salle_id` (`salle_id`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evenement_id` (`evenement_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `salles`
--
ALTER TABLE `salles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `administrateurs`
--
ALTER TABLE `administrateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `evenements`
--
ALTER TABLE `evenements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `methode_paiement`
--
ALTER TABLE `methode_paiement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `photos_salles`
--
ALTER TABLE `photos_salles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `salles`
--
ALTER TABLE `salles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `evenements`
--
ALTER TABLE `evenements`
  ADD CONSTRAINT `evenements_ibfk_1` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`),
  ADD CONSTRAINT `evenements_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `photos_salles`
--
ALTER TABLE `photos_salles`
  ADD CONSTRAINT `photos_salles_ibfk_1` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`evenement_id`) REFERENCES `evenements` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
