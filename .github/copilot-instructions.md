# So'Sand - AI Coding Assistant Instructions

## Project Overview
This is a Symfony 7.4 e-commerce application for a candle shop (So'Sand). It features user authentication, product catalog with categories/subcategories, shopping cart (persistent for logged-in and anonymous users), Stripe payment integration, and order management.

## Architecture
- **Framework**: Symfony 7.4 with MicroKernel
- **Database**: Doctrine ORM with MySQL
- **Frontend**: Twig templates, Bootstrap 4, Stimulus.js + Turbo
- **Assets**: AssetMapper (CSS/JS management)
- **Payment**: Stripe Checkout with webhooks
- **Email**: Symfony Mailer with Twig templates

## Key Components
- **Entities**: User, Article (products), Order, Panier (cart), Categorie/SousCategorie, Facture, LigneFacture
- **Services**: PanierService (cart logic), OrderMailer (email notifications)
- **Controllers**: RESTful with attribute routing, FactureController (invoice listing)
- **Security**: Symfony Security with roles, remember-me tokens

## Critical Patterns

### Cart Management
- **Anonymous users**: Session-based with cookie `panier_session_id`
- **Logged-in users**: DB-persisted with automatic merge from session cart
- **Stock handling**: JSON-based size/variant inventory in Article entity
- **Price calculation**: TTC (including VAT) with shipping costs

### Payment Flow
- **Order creation**: Before Stripe session (status: PENDING)
- **Stripe integration**: Checkout sessions with metadata (order_id, user_id)
- **Webhook handling**: Asynchronous status updates to PAID
- **Success fallback**: Manual status update if webhook delayed
- **Invoice generation**: Automatic creation after payment with unique numbers

### Product Structure
- **Variants**: Sizes with price/stock stored as JSON in `tailles` field
- **Relationships**: Many-to-many with Parfum (scents) and Couleur (colors)
- **Images**: One-to-many Photo entities with filename storage

### Invoice System
- **Entities**: Facture (invoice) and LigneFacture (invoice lines) with immutable data
- **Numbering**: Unique format F2025XXXXXX (year + padded ID)
- **Data storage**: Customer details, items, quantities, prices in TTC (no VAT)
- **Creation**: Automatic after successful payment in success() method

### Development Workflow
- **Migrations**: Doctrine migrations for schema changes
- **Assets**: `importmap:install` for JS dependencies
- **Testing**: PHPUnit with test database
- **Debugging**: Symfony Profiler, custom Stripe webhook logging

## Conventions
- **Entity validation**: Assert constraints on properties
- **Service injection**: Constructor injection in controllers
- **Template inheritance**: Base layout with blocks
- **Error handling**: Try-catch with flash messages
- **Date handling**: Immutable DateTime for audit fields
- **User pages**: Dedicated pages (factures, favoris) without left sidebar menu

## Common Tasks
- **Adding products**: Create Article entity with JSON tailles structure
- **Cart operations**: Use PanierService methods with proper session handling
- **Payment integration**: Always create order before Stripe session
- **Email sending**: Use OrderMailer service with TemplatedEmail
- **Asset management**: Place in `assets/` directory, use AssetMapper
- **Invoice generation**: Automatic after payment, immutable data storage

## Security Notes
- **CSRF protection**: Enabled on forms
- **Input validation**: Entity constraints + form validation
- **Payment security**: Webhook signature verification
- **Session management**: Secure cookie handling for anonymous carts

## File Organization
- `src/Entity/`: Doctrine entities with relationships
- `src/Controller/`: Route handlers with business logic
- `src/Service/`: Reusable business services
- `templates/`: Twig views organized by feature
- `migrations/`: Database schema changes
- `config/`: Symfony configuration files