**Soluta** is a comprehensive business management system built with Laravel 12, featuring advanced inventory management, transaction processing, and subscription management capabilities.

## Core Features

**Transaction Management**
- Multiple transaction types: Purchase, Sale, Machinery Purchase/Sale/Rent, Production, Advisory
- Real-time stock management with `StockService`
- Return processing and stock adjustments
- Payment method handling with `TransactionPayment` model
- Due management and payment tracking
- Transaction audit trail with `TransactionLog`

**Inventory System**
- Product catalog with categories and units (`Product`, `ProductCategory`, `BaseUnit`)
- Stock ledger tracking with `StockLedger`
- Multi-unit support with `UnitOption` and conversions
- Production capability for products
- Recent price tracking with `ProductRecentPrice`

**Subscription Management** (NEW)
- Custom subscription package: `soluta/subscription`
- Plan management with billing cycles and grace periods
- Subscription renewals and feature consumption
- Stripe integration with webhook handling
- License management system with `License`, `LicenseHistory`, `LicenseRequest`

**Payment Service**
- Flexible payment processing with multiple gateway support
- Country-based payment gateway routing:
    - Automatic gateway selection based on user country ISO code
    - Factory pattern for gateway instantiation via [PaymentGatewayFactory]
    - Currently supports Stripe (global) and Bkash (Bangladesh)
    - Extensible architecture - add new gateways by implementing [PaymentGateway] interface

**Notification Service**
- Multi-channel notification system with OTP generation and delivery
- Channel-based routing: SMS and Email support via factory pattern
- Country-based SMS gateway routing:
    - Automatic country detection from phone number using `propaganistas/laravel-phone`
    - Factory pattern for SMS provider selection for different country via [SMSFactory]
    - Currently supports Bangladesh (BD) via SSL Wireless gateway
- Purpose-based message templates (Signup, Forgot Password, Email Update)

**User Management**
- Authentication with Laravel Sanctum
- User profiles with social accounts
- Contact management for business relationships
- Customer summaries with `CustomerSummery`
- Role-based access control with user types and statuses


## Technical Architecture

**Backend Framework**: Laravel 12 with PHP 8.2+
**Database**: PostgreSQL with comprehensive migrations
**API**: RESTful API with middleware authentication
**Package Management**: Custom subscription package with local path repository

**Key Models**
- `Transaction`, `TransactionItem`, `TransactionPayment`, `TransactionLog` - Core transaction handling
- `Product`, `ProductUser`, `StockLedger` - Inventory management
- `Contact`, `CustomerSummery` - Business contact management
- `License`, `LicenseHistory`, `LicenseRequest` - License management
- [Subscription](cci:9://file:///home/mohiudin/Documents/tarikul/office/2026/soluta-v2/packages/Soluta/Subscription:0:0-0:0), `Plan` - Subscription management (from custom package)

**Key Services**
- `StockService` - Inventory management and stock calculations
- `ReturnService` - Return transaction processing
- `PaymentService` - Payment handling
- `TransactionLogService` - Audit logging
- `SubscriptionService` - Subscription management
- Stripe webhook handling and payment initiation

**Key Enums**
- `TxnType`, `PaymentMethod`, `PaymentStatus` - Transaction types and payment
- `StockEntryType`, `StockStatus` - Inventory management
- `ContactType`, `UserType`, `UserStatus` - User management
- `GatewayType`, `OtpPurpose` - System utilities

## Development Environment

**Dependencies**:
- Laravel 12.0 with PHP 8.2+
- Laravel Horizon for queue management
- Laravel Telescope for debugging
- Laravel Sanctum for API authentication
- Stripe PHP SDK for payments
- Google API Client integration

**Development Tools**:
- Pest for testing
- Laravel Pint for code formatting
- Docker support with multi-service setup
- Concurrent development server setup

The project follows Laravel best practices with proper separation of concerns, enum-based type safety, comprehensive database relationships, and a modular architecture with custom packages for specialized functionality.