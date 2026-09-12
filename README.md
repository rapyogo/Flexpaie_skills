# FlexPay Mobile Money — Integration Skill 🚀

> **Conçu et partagé par : Michel Bengana**
> 
> *Ce guide est une architecture universelle pour intégrer FlexPay.cd Mobile Money dans TOUS les langages (PHP, Node.js, Python, Java...). Il inclut des implémentations complètes pour **PHP natif**, **Next.js 14+ App Router**, **Laravel avec Firestore** (pattern Viteat 9.2), et un **Package complet Laravel 10/11/12 avec Eloquent SQL**.*

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20%7C%2012.x-red)](https://laravel.com)
[![Next.js](https://img.shields.io/badge/Next.js-14%2B-black)](https://nextjs.org)

---

## 🇨🇩 Contexte

FlexPay.cd est la passerelle de paiement de référence en République Démocratique du Congo (RDC). Elle supporte :
- **Mobile Money** : Airtel Money, M-Pesa (Vodacom), Orange Money, AfriMoney (Africell)
- **Carte bancaire** : Visa, Mastercard
- **Merchant Pay Out** : Transfert vers un compte Mobile Money

Ce repo contient le **skill d'intégration complet** — de l'architecture et la sécurité jusqu'au code source prêt à l'emploi.

---

## ⚠️ Règle d'Or de Production : Gestion du Statut API (`status: "2"`)

> **ATTENTION AUX LATENCES RÉSEAU USSD EN RDC :**
> - **`status: "0"`** ➔ **Succès confirmé** (débit validé par l'opérateur).
> - **`status: "2"`** ➔ **En attente de saisie du PIN** (`"Le paiement est en attente"`).
> - **`status: "1"`** ➔ **Échec réel** (rejet, solde insuffisant ou expiration).
>
> ❌ **Erreur classique :** Traiter `status: "2"` comme une anomalie ou un échec interrompt le polling après 3 secondes. L'utilisateur clique à répétition, provoquant un **verrouillage USSD de 15 minutes (900s)** sur la passerelle télécom de l'opérateur (Airtel / Vodacom).
> 
> ✅ **Solution :** Toujours maintenir un polling non-bloquant de 3 secondes pendant au moins **180 secondes (3 minutes)** et fournir un bouton *"Vérifier à nouveau"* sans ré-initier.

---

## 🔄 Flux de paiement

```
Acheteur → Formulaire checkout → API FlexPay → Push téléphone (PIN) → Confirmation
                                                                            ↓
                         Callback ou Polling → Finalisation → Commande Validée
```

---

## 📦 Ce que contient ce repo

| Dossier | Contenu |
|---------|---------|
| [`SKILL.md`](./SKILL.md) | Skill complet — architecture universelle, sécurité, Firestore et package Laravel |
| [`examples/laravel/`](./examples/laravel/) | **Package complet Laravel 10/11/12** (Service, Controller, Middleware IP, Model, Migrations, Views Blade, Tests) |
| [`examples/php/`](./examples/php/) | Service PHP pur (sans framework) |
| [`examples/nextjs/`](./examples/nextjs/) | Route Handlers + Client Components Next.js 14+ App Router |
| `src/`, `config/`, `database/`, `resources/` | Sources du package Laravel à la racine pour installation Composer |

---

## 🚀 Démarrage rapide

### Laravel (10.x, 11.x, 12.x)

```bash
# 1. Utiliser le package directement ou copier les fichiers d'exemples
composer require flexpay/flexpay-laravel

# Ou copier manuellement depuis examples/laravel :
cp -r examples/laravel/src app/FlexPay
cp examples/laravel/config/flexpay.php config/

# 2. Configurer votre .env
FLEXPAY_MERCHANT_CODE=SIMULATED       # 'SIMULATED' pour tester sans carte SIM
FLEXPAY_API_TOKEN=votre_token_flexpay
FLEXPAY_API_URL=https://backend.flexpay.cd/api/rest/v1
FLEXPAY_CALLBACK_URL=https://votredomaine.cd/payments/flexpay/callback
FLEXPAY_IP_WHITELIST=156.0.198.27,156.0.198.19
FLEXPAY_STRICT_IP=true

# 3. Exécuter les migrations
php artisan migrate
```

Utilisation dans votre code :

```php
use FlexPay\Laravel\Facades\FlexPay;

// Initiation
$result = FlexPay::initiateMobileMoney(
    phone: '0973604485',
    reference: 'CMD-1024',
    amount: 10.00,
    currency: 'USD'
);

// Dans vos vues Blade
<x-flexpay::payment-modal :amount="10.00" currency="USD" reference="CMD-1024" />
```

---

### PHP (pur, sans framework)

```bash
# 1. Copier les fichiers
cp examples/php/FlexPayService.php app/Services/
cp examples/php/CheckoutController.php app/Controllers/
cp examples/php/CallbackController.php app/Controllers/

# 2. Configurer .env
FLEXPAY_MERCHANT_CODE=SIMULATED
FLEXPAY_API_URL=https://backend.flexpay.cd/api/rest/v1
FLEXPAY_API_TOKEN=votre_token_jwt
FLEXPAY_CALLBACK_URL=https://votredomaine.cd/callback/flexpay
```

---

### Next.js 14+ (App Router)

```bash
# 1. Copier le service et les Route Handlers
cp examples/nextjs/flexpay.ts lib/flexpay.ts
cp examples/nextjs/finalize.ts lib/finalize.ts
cp -r examples/nextjs/api app/api/

# 2. Configurer .env.local
FLEXPAY_MERCHANT_CODE=SIMULATED
FLEXPAY_API_URL=https://backend.flexpay.cd/api/rest/v1
FLEXPAY_API_TOKEN=votre_token_jwt
FLEXPAY_CALLBACK_URL=http://localhost:3000/api/callback/flexpay
```

---

## 🛡️ Sécurité & Bonnes Pratiques

- ✅ **Whitelisting IP strict** des serveurs FlexPay (`156.0.198.27`, `156.0.198.19`) avec support `TrustProxies`.
- ✅ **Exclusion CSRF ciblée** uniquement sur le webhook `payments/flexpay/callback`.
- ✅ **Protection anti-rejeu et concurrence** via transition atomique (`WHERE status = 'pending'`).
- ✅ **Vérification de cohérence du montant** reçu vs attendu (tolérance 0.01).
- ✅ **Détection d'opérateur en temps réel** : Airtel (`097, 098, 099`), Vodacom (`081, 082, 083`), Orange (`080, 084, 085, 089`), Africell (`090, 091`).

---

## 📄 Licence

MIT — Utilisez librement ce code dans vos projets commerciaux ou open-source.