# 🔪 itwillcut - Bandeau légal couteaux pour PrestaShop 9

[![PrestaShop](https://img.shields.io/badge/PrestaShop-9.0+-blue.svg)](https://www.prestashop.com/)
[![PHP](https://img.shields.io/badge/PHP-8.3+-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-AFL--3.0-green.svg)](LICENSE.txt)
[![Build](https://img.shields.io/badge/Build-Webpack-orange.svg)](_dev/webpack.config.js)

Module PrestaShop 9 dédié à la conformité légale pour la vente de couteaux : affichage d’un bandeau d’interdiction de vente aux mineurs en pied de page et rappel lors du passage de commande.

---

## ✨ **Fonctionnalités**

- ✅ Bandeau d’information légal dans le footer
- ✅ Rappel explicite dans le tunnel de commande avant paiement
- ✅ Compatible thème Ceramiry (ThemeVolty)
- ✅ Système de traduction PrestaShop 9 (fichiers XLF)
- ✅ Code PHP typé, commenté, architecture Legacy
- ✅ JS/CSS séparés, minifiés via Webpack
- ✅ Tests unitaires, fonctionnels et d’intégration (_dev/tests)

---

## 🚀 **Installation**

### 1. **Installation du module**

```bash
# Copier le module dans /modules/
cp -r itwillcut /var/www/html/modules/

# Activer depuis le back-office PrestaShop
# Modules > Gestionnaire de modules > Rechercher "itwillcut"
```

### 2. **Configuration des assets**

```bash
cd modules/itwillcut/_dev

# Installer les dépendances
npm install

# Développement (watch mode)
npm run dev

# Production (minification)
npm run build
```

---

## 🛠️ **Configuration**

1. Back-office → Modules → itwillcut → Configurer
2. Personnaliser le texte du bandeau si besoin
3. Vérifier l’affichage sur le front et le tunnel de commande

---

## 🏗️ **Structure du projet**

```
itwillcut/
├── _dev/                  # Développement (JS, SCSS, tests, build)
│   ├── js/
│   ├── scss/
│   ├── tests/
│   ├── package.json
│   └── webpack.config.js
├── classes/               # Classes métier PHP
├── controllers/           # Contrôleurs front et admin
├── sql/                   # Scripts SQL d’installation
├── translations/          # Traductions (XLF)
│   └── fr-FR/
├── upgrade/               # Scripts de mise à jour
├── views/                 # Assets compilés et templates Smarty
│   ├── css/
│   ├── js/
│   └── templates/
│       ├── admin/
│       └── front/
│           ├── checkout_notice.tpl
│           └── footer_banner.tpl
├── itwillcut.php          # Fichier principal du module
└── README.md              # Documentation
```

---

## 🧪 **Tests**

### **Lancer les tests**

```bash
cd _dev/tests

# Installer PHPUnit si besoin
composer install

# Exécuter tous les tests
./vendor/bin/phpunit

# Couverture de code
./vendor/bin/phpunit --coverage-html coverage-html/
```

---

## 📊 **Base de données**

Aucune table spécifique créée : le module se contente d’afficher des informations légales, sans journalisation.

---

## ⚡ **Performance**

- ✅ JS/CSS minifiés via Webpack
- ✅ Chargement asynchrone des scripts
- ✅ Compatible mobile et responsive

---

## 🔧 **Personnalisation**

### **CSS**

```scss
// Surcharger les variables dans votre thème
$banner-bg-color: #your-brand-color;
$banner-text-color: #fff;

// Importer le CSS du module
@import "modules/itwillcut/views/css/footer_banner.css";
```

### **Smarty**

```smarty
{* Surcharger dans themes/votre-theme/modules/itwillcut/ *}
{extends file='modules/itwillcut/views/templates/front/footer_banner.tpl'}

{block name="banner_text"}
    <span>{l s='La vente de couteaux est interdite aux mineurs.' mod='itwillcut'}</span>
{/block}
```

---

## 🛠️ **Développement**

- ✅ [PrestaShop 9 Coding Standards](https://devdocs.prestashop-project.org/9/development/coding-standards/)
- ✅ [PSR-12](https://www.php-fig.org/psr/psr-12/) pour PHP (hors autoload PSR-4)
- ✅ ESLint pour JS, StyleLint pour SCSS
- ✅ Architecture Legacy, typage et PhpDoc
- ✅ Système de traduction PrestaShop 9

---

## 📚 **Documentation**

- 📖 [Documentation PrestaShop 9](https://devdocs.prestashop-project.org/9/)
- 🔪 [Législation vente couteaux](https://www.service-public.fr/particuliers/vosdroits/F31728)

---

## 🤝 **Support**

### **Signaler un bug**

1. Vérifier les [issues existantes](https://github.com/your-repo/issues)
2. Créer une nouvelle issue avec :
   - Version PrestaShop
   - Version PHP
   - Navigateur et version
   - Erreurs JS/PHP

### **Demander une fonctionnalité**

- 💡 [Créer une Feature Request](https://github.com/your-repo/issues/new?template=feature_request.md)
- 💬 [Discussions](https://github.com/your-repo/discussions)

---

## 📄 **Licence**

Ce module est distribué sous [Academic Free License 3.0 (AFL-3.0)](LICENSE.txt).

---

## 🏆 **Contributeurs**

- **Atelier-Legoff** - Développement & maintenance

---

**⭐ Si ce module vous aide, laissez une étoile !**

*Développé avec ❤️ pour la communauté PrestaShop et les professionnels de la coutellerie.*

---
