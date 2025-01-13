# Up GSAP Animate

Un plugin WordPress qui ajoute des animations GSAP à n'importe quel bloc dans l'éditeur.

## Fonctionnalités

- Ajout d'animations à n'importe quel bloc WordPress
- Plusieurs types d'animations (fondu, glissement, échelle, rotation)
- Durée et délai personnalisables
- Animations déclenchées au défilement
- Contrôles de bloc faciles à utiliser dans l'éditeur
- Animations responsives
- Performances optimisées

## Options de Déclenchement

Le plugin offre plusieurs options pour déclencher les animations :

### Types de Déclenchement

1. **Scroll** - Déclenche l'animation au défilement
   - Start Position : Position de début (ex: "top center", "50% 75%")
   - End Position : Position de fin (optionnel)
   - Scrub Type :
     - None : Animation normale
     - True : Animation liée au défilement
     - Smooth : Animation fluide avec contrôle de la fluidité
   - Pin Element : Fixe l'élément pendant l'animation
   - Show Markers : Affiche les marqueurs de débogage

2. **Load** - Déclenche l'animation au chargement de la page

3. **Click** - Déclenche l'animation au clic

4. **Hover** - Déclenche l'animation au survol
   - Reverse on Leave : Inverse l'animation quand la souris quitte l'élément

5. **Custom** - Déclenchement personnalisé
   - Custom Trigger : Sélecteur CSS ou ID d'élément personnalisé

## Installation

1. Téléchargez les fichiers du plugin dans le répertoire `/wp-content/plugins/up-gsap-animate`
2. Activez le plugin via l'écran 'Extensions' dans WordPress
3. Utilisez l'éditeur de blocs pour ajouter des animations à vos blocs

## Développement

### Prérequis Système

- PHP 7.4 ou supérieur
- WordPress 6.0 ou supérieur
- Node.js (v14 ou supérieur)
- npm (v6 ou supérieur)
- Environnement de développement WordPress local (Local, MAMP, etc.)

### Dépendances

Le plugin utilise les dépendances suivantes :

#### Dépendances de Production
- `gsap` (v3.12.4) - Bibliothèque d'animation GSAP

#### Dépendances de Développement
- `@wordpress/env` (v10.15.0) - Environnement de développement WordPress
- `@wordpress/scripts` (v30.8.1) - Scripts de build WordPress

### Installation et Configuration

1. Assurez-vous d'avoir Node.js et npm installés sur votre système :
   ```bash
   node --version  # Doit être v14 ou supérieur
   npm --version   # Doit être v6 ou supérieur
   ```

2. Clonez ce dépôt dans votre répertoire de plugins WordPress :
   ```bash
   cd wp-content/plugins
   git clone [url-du-depot] up-gsap-animate
   ```

3. Installez les dépendances du projet :
   ```bash
   cd up-gsap-animate
   npm install
   ```

4. Vérifiez que toutes les dépendances sont correctement installées :
   ```bash
   npm list --depth=0
   ```

### Commandes Disponibles

- `npm run start` - Lance le mode développement avec rechargement à chaud
- `npm run build` - Compile la version de production du plugin
- `npm run format` - Formate tout le code selon les standards WordPress
- `npm run lint:css` - Vérifie les fichiers CSS
- `npm run lint:js` - Vérifie les fichiers JavaScript
- `npm run plugin-zip` - Crée un fichier zip du plugin pour la distribution
- `npm run packages-update` - Met à jour les paquets WordPress vers leurs dernières versions
- `npm run env` - Gère l'environnement de développement WordPress

### Workflow de Développement

1. Démarrer le serveur de développement :
   ```bash
   npm run start
   ```
   Cela surveillera les modifications de fichiers et recompilera automatiquement si nécessaire.

2. Pour la compilation en production :
   ```bash
   npm run build
   ```
   Cela créera des fichiers optimisés dans le répertoire `build`.

3. Pour créer un fichier zip distribuable :
   ```bash
   npm run plugin-zip
   ```

### Résolution des Problèmes Courants

Si vous rencontrez des erreurs lors de l'installation ou de la compilation :

1. Vérifiez que vos versions de Node.js et npm sont à jour
2. Supprimez les dossiers `node_modules` et `build`
3. Réinstallez les dépendances :
   ```bash
   rm -rf node_modules build
   npm install
   npm run build
   ```

## Contribution

Les contributions sont les bienvenues ! N'hésitez pas à soumettre une Pull Request.

## Licence

GPL v2 ou ultérieure
