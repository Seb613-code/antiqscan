# AntiQScan — comptes utilisateurs et bibliothèque Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Permettre à chaque utilisateur de créer son compte, de ne voir et gérer que ses propres fiches, puis de retrouver ses livres rapidement depuis une page d’accueil structurée.

**Architecture:** Utiliser l’authentification de session Laravel déjà configurée. Ajouter `books.user_id` avec index et clé étrangère, appliquer la propriété dans chaque action de fiche/image/export, et remplacer le mot de passe partagé par inscription, connexion et déconnexion. L’index charge deux collections privées : les fiches à relire et une bibliothèque complète filtrable/triable côté navigateur.

**Tech Stack:** Laravel 12, Blade, SQLite, Eloquent, PHPUnit, Tailwind, JavaScript minimal sans dépendance.

---

### Task 1: Propriété des fiches

**Files:** migration `database/migrations/*_add_user_id_to_books_table.php`, `app/Models/Book.php`, `app/Models/User.php`, `app/Http/Controllers/BookController.php`, tests.

1. Écrire un test démontrant qu’un utilisateur ne peut ni consulter, ni modifier, ni supprimer une fiche appartenant à un autre.
2. Vérifier l’échec sur l’application actuelle.
3. Ajouter la relation `Book belongsTo User`, `User hasMany Book`, la colonne indexée `user_id` et les contrôles de propriété dans les routes/actions.
4. Lors de l’import, associer la fiche à l’utilisateur authentifié.
5. Rejouer le test puis la suite complète.

### Task 2: Inscription et authentification

**Files:** `app/Http/Controllers/AuthController.php`, `resources/views/auth/*.blade.php`, `routes/web.php`, `bootstrap/app.php`, `tests/Feature/*`.

1. Écrire les tests d’inscription, connexion, déconnexion et accès anonyme refusé.
2. Vérifier leur échec.
3. Ajouter formulaires et actions Laravel `Auth::login`/`Auth::attempt`, avec validation email unique et mot de passe de 12 caractères minimum.
4. Remplacer le middleware de mot de passe partagé par `auth` sur les routes privées.
5. Rejouer tests ciblés et suite complète.

### Task 3: Écran d’accueil à trois surfaces

**Files:** `BookController@index`, `resources/views/books/index.blade.php`, `resources/css/app.css`, tests.

1. Écrire un test garantissant que la page affiche import, « Fiches à relire » et « Votre bibliothèque », et que les fiches validées sont exclues du bloc à relire.
2. Vérifier l’échec.
3. Charger séparément les fiches à relire et la bibliothèque complète de l’utilisateur courant.
4. Garder la carte d’import, créer une carte dédiée aux fiches à relire et une table bibliothèque.
5. Rejouer les tests.

### Task 4: Table bibliothèque triable et filtrable

**Files:** `resources/views/books/index.blade.php`, `resources/css/app.css`, tests front-end/feature pertinents.

1. Écrire le test de présence des colonnes Auteur, Titre, Date, contrôles de recherche/filtre et boutons Voir la fiche/Supprimer.
2. Vérifier l’échec.
3. Ajouter une table à conteneur horizontal défilable, recherche texte et filtre de statut ; tri ascendant/descendant par colonnes via JavaScript minimal, accessible par boutons.
4. Ajouter `data-*` fiables (auteur, titre, date, statut) et ne jamais afficher les fiches d’un autre utilisateur.
5. Vérifier la page rendue et `node --check` sur le script extrait si nécessaire.

### Task 5: Revue et livraison

1. Lancer PHPUnit complet et Pint sur les fichiers modifiés.
2. Examiner migrations, routes, code de propriété et diff pour sécurité / fuite inter-utilisateur.
3. Compiler les assets.
4. Commit, push, puis déployer seulement après revue de sécurité de la configuration production.
