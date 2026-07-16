# AntiQScan — Refonte frontend documentaire — Plan d’implémentation

> **Pour Hermes :** exécuter avec la discipline `subagent-driven-development`, tests avant tout comportement vérifiable, puis revue séparée.

**But :** transformer les trois écrans AntiQScan (bibliothèque, relecture, fiche catalogue) en une interface documentaire moderne, chaleureuse et très pratique, sans ajouter de dépendance ni modifier le pipeline métier.

**Architecture :** Laravel Blade + Tailwind existant. Les choix visuels récurrents seront centralisés dans `resources/css/app.css` via tokens CSS et petites classes sémantiques. Les vues restent rendues côté serveur ; seul le JavaScript de prévisualisation de l’upload est conservé. Les statuts sont dérivés des données existantes, sans migration.

**Direction :** ivoire / encre / vert bibliothèque ; sans-serif pour l’interface, sérif uniquement pour la consultation de catalogue. Référence : documentation éditoriale sobre, pas faux-vintage, pas de dark mode, pas d’illustration générée.

**Contraintes non négociables :**
- Le catalogue et ses sources ne doivent toujours afficher que les données validées.
- Aucun changement du contrat de routes, de l’upload ou du pipeline IA.
- Interface exploitable sans JavaScript, hors aperçu local de l’image.
- Responsive : pas de tableau horizontal imposé pour l’essentiel de la bibliothèque sur mobile.
- Ne pas exposer de debug ni de vocabulaire technique/pipeline à l’utilisateur final.

---

### Tâche 1 — Socle visuel et shell global

**Fichiers :**
- Modifier : `resources/css/app.css`
- Modifier : `resources/views/components/layout.blade.php`

**Objectif :** définir les tokens (fond, surfaces, bordures, texte, accent, rayons, ombres) et un shell cohérent avec en-tête global compact.

**Implémentation :**
1. Conserver Tailwind ; ajouter les tokens dans `@theme`/`:root` et des classes sémantiques légères (`page-shell`, `section-card`, `button-primary`, `button-secondary`, `button-danger`, `meta-label`, `status-badge`, `document-sheet`, `form-input`, `form-textarea`).
2. Remplacer les CTA noirs par l’accent vert bibliothèque ; conserver danger distinct.
3. Ajouter dans le layout une marque AntiQScan reliée à la bibliothèque, et un conteneur responsive plus généreux.
4. Ajouter une police sérif web légère pour la seule fiche catalogue, ou utiliser un fallback système si le chargement externe n’est pas souhaitable.

**Vérification :** `npm run build`, puis inspection navigateur desktop et mobile.

### Tâche 2 — Bibliothèque orientée action et responsive

**Fichiers :**
- Modifier : `resources/views/books/index.blade.php`
- Test : `tests/Feature/BookIntakeFlowTest.php`

**Objectif :** rendre immédiatement compréhensibles création, état de relecture et action suivante de chaque fiche.

**RED :** ajouter un test de rendu qui confirme l’affichage d’un statut lisible et du CTA approprié pour une fiche créée/extraites et une fiche validée.

**GREEN :**
1. Créer une zone d’introduction fonctionnelle avec le parcours « Importer → Relire → Consulter ».
2. Faire de l’upload la carte principale, avec une zone de prévisualisation propre et une microcopie explicite.
3. Remplacer la table par une liste de cartes hybrides : miniature, citation, statut, dernière modification et CTA principal.
4. Dériver sans migration les statuts : `Validée` si `user_validated_at` est défini ; sinon `À relire`.
5. Action principale : `Voir le catalogue` pour validée ; `Relire la fiche` sinon. La suppression reste secondaire et confirmée.
6. Après création, afficher une confirmation qui dit explicitement que les champs doivent être relus, avec lien vers la fiche.

**Vérification :** test ciblé puis suite PHPUnit complète ; navigateur à largeur mobile et desktop.

### Tâche 3 — Écran de relecture orienté objet, puis fiche catalogue raffinée

**Fichiers :**
- Modifier : `resources/views/books/show.blade.php`
- Modifier : `resources/views/books/partials/field-row.blade.php`
- Modifier : `resources/views/books/catalogue.blade.php`
- Test : `tests/Feature/BookIntakeFlowTest.php` si la microcopie/règle métier est rendue

**Objectif :** faire de la page d’édition un vrai espace de relecture humaine, et de la fiche finale un document de consultation.

**Implémentation :**
1. Ajouter un en-tête de fiche contenant numéro, citation provisoire, statut et actions ; mettre le CTA `Enregistrer les modifications` en évidence.
2. Sur desktop, mettre l’image et l’état de travail dans une colonne latérale ; laisser le formulaire comme colonne principale. Empiler proprement en mobile.
3. Renommer les blocs : `Informations bibliographiques repérées`, `Notice de catalogue`, `Description matérielle`, `Sources à vérifier`.
4. Simplifier l’affichage des sources, masquer visuellement la zone d’estimation vide au lieu d’en faire une card.
5. Donner aux lignes de formulaire des labels lisibles et des champs cohérents avec les nouvelles classes.
6. Donner à la fiche catalogue une largeur de lecture, une typographie documentaire et des séparateurs discrets ; préserver strictement le rendu validé/externe approuvé.

**Vérification :** PHPUnit complet ; `npm run build`; inspection navigateur de `/`, `/books/{id}`, `/books/{id}/catalogue`, en largeur mobile et desktop.

### Gate de révision

- **Pré-vol :** arbre Git propre et tests de base verts avant modification.
- **Révision :** Forge rend diff + tests + build ; Patch relit conformité fonctionnelle ; Surmoi relit risques UX/accessibilité. Deux itérations maximum.
- **Escalade :** uniquement si une nouvelle dépendance, une migration, ou un changement de contrat métier devient nécessaire.
- **Déploiement :** seulement après tests, build, diff propre et avis Surmoi sans risque bloquant.
