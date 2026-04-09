# Portfolio Online Store

A lightweight, clean e-commerce system built with **PHP 8+**, **SQLite (PDO)**, and **PayPal REST API v2**.

## Features

| Area | Details |
|---|---|
| Product listing | Responsive grid, category filtering, keyword search, pagination |
| Product detail | Full description, images, add-to-cart with quantity |
| Cart | Session-based (no login required), add / update / remove items |
| Checkout | Customer name + email → PayPal redirect |
| PayPal | Sandbox & Live modes, server-side capture & verification |
| Admin | Dashboard, products CRUD, order management |
| Security | CSRF tokens, PDO prepared statements, server-side total recalculation, XSS escaping |

## Quick Start

### 1. Configure

Open `config.php` and update:

```php
// PayPal credentials (from developer.paypal.com)
define('PAYPAL_CLIENT_ID', 'your-client-id');
define('PAYPAL_SECRET',    'your-secret');
define('PAYPAL_MODE',      'sandbox'); // 'sandbox' | 'live'

// Currency
define('CURRENCY', 'GBP'); // change to 'USD', 'EUR', etc.
```

### 2. Run setup

Visit `/setup.php` in your browser to create the first admin account.  
**Delete `setup.php` after use.**

### 3. Add products

Log in at `/login.php` → Admin → Products → Add Product.

---

## Folder Structure

```
store/
├── index.php           # Product listing (homepage)
├── product.php         # Single product page
├── cart.php            # Shopping cart
├── checkout.php        # Checkout (captures details, creates PayPal order)
├── success.php         # PayPal return URL – captures & verifies payment
├── cancel.php          # PayPal cancel URL – handles abandoned payments
├── login.php           # Admin login
├── logout.php          # Session destroy
├── setup.php           # One-time admin account creation (delete after use)
├── config.php          # All configuration
├── functions.php       # Shared helpers (cart, products, orders, CSRF …)
│
├── admin/
│   ├── auth.php            # Authentication guard (included by every admin page)
│   ├── index.php           # Dashboard (stats + recent orders)
│   ├── products.php        # Product listing + delete
│   ├── create_product.php  # Add product
│   ├── edit_product.php    # Edit product
│   └── orders.php          # Order list + detail + status update
│
├── payments/
│   └── paypal.php      # PayPal REST API v2 (token, create order, capture, verify)
│
├── db/
│   ├── schema.php      # SQLite CREATE TABLE statements (auto-run on first request)
│   └── store.sqlite    # Created automatically by PDO on first request
│
├── templates/
│   ├── header.php      # HTML <head> + sticky nav + cart icon
│   ├── footer.php      # Site footer + JS include
│   ├── product_card.php  # Reusable product grid card
│   ├── cart_item.php     # Reusable cart table row
│   └── admin_nav.php     # Admin sidebar navigation
│
└── assets/
    ├── css/style.css   # Full store stylesheet (white + orange, Inter font)
    ├── js/main.js      # Nav toggle, quantity spinners, flash dismissal
    └── images/         # Uploaded product images
```

---

## PayPal Integration

The payment system is intentionally **modular**:

- `payments/paypal.php` — all PayPal-specific code
- `checkout.php` calls `paypalCreateOrder()` and redirects to PayPal
- `success.php` calls `paypalCaptureOrder()` and `paypalIsPaymentComplete()` server-side

To add a second provider (e.g. Stripe), create `payments/stripe.php` with the same public function signatures and add a provider switch in `checkout.php`.

### PayPal flow

```
Customer fills checkout form
        │
        ▼
createOrder() → DB order (status: pending)
        │
        ▼
paypalCreateOrder() → PayPal Orders API
        │
        ▼
Redirect to PayPal approval URL
        │
        ▼  (customer pays)
PayPal redirects to success.php?token=PAYPAL_ORDER_ID
        │
        ▼
paypalCaptureOrder() server-side
        │
        ▼
paypalIsPaymentComplete() verification
        │
        ▼
updateOrderPayment() → DB order (status: paid)
Cart cleared ✓
```

---

## Database

SQLite via PDO. Tables are created automatically on first request by `db/schema.php`.

| Table | Purpose |
|---|---|
| `users` | Admin accounts (hashed passwords) |
| `categories` | Product categories |
| `products` | Products (name, price, stock, image, status) |
| `orders` | Customer orders with payment status + PayPal transaction ID |
| `order_items` | Line items linking orders to products |

---

## Security

- All queries use **PDO prepared statements** (no SQL injection)  
- **CSRF tokens** on every POST form  
- **Server-side total recalculation** (never trusts client prices)  
- **PayPal capture verified server-side** before marking orders as paid  
- **Session token matching** prevents PayPal order ID swap attacks  
- Input validation and **`htmlspecialchars`** output escaping (XSS prevention)  
- Passwords hashed with **`password_hash(PASSWORD_BCRYPT)`**  
- Session cookies: `httponly`, `secure`, `samesite=Strict`  

---

## Requirements

- PHP 8.0+ with PDO, PDO_SQLite, cURL, and JSON extensions
- A web server (Apache / Nginx / PHP built-in dev server)
- PayPal developer account (sandbox) or live account

---

*Built by Snat · [terra.me.uk](https://terra.me.uk)*
