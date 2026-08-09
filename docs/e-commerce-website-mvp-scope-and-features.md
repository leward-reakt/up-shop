# E-Commerce Website MVP Features & Scope

## Overview

The E-Commerce MVP is a production-ready online shopping platform that allows customers to browse products, manage their cart, place orders, make payments, and track their purchases.

Administrators can manage products, inventory, customers, orders, payments, discounts, and basic store configuration through a secure admin dashboard.

The MVP focuses on the essential features required to launch and operate an online store while avoiding unnecessary complexity and scope creep.

---

## 1. Customer Authentication

### Scope

- Customer registration
- Customer login
- Customer logout
- Forgot password
- Reset password
- Change password
- Basic customer profile
- Secure password hashing
- Email validation
- Authentication session management

---

## 2. Product Catalog

### Scope

- Product listing
- Product categories
- Product name
- Product slug
- SKU
- Product description
- Product price
- Product images
- Product availability
- Product status
- Featured products
- Category assignment

#### Product Information

Each product may contain:

- Name
- SKU
- Slug
- Description
- Price
- Main image
- Additional images
- Category
- Stock quantity
- Active/inactive status
- Featured status

---

## 3. Product Details

### Scope

- Product name
- Product image gallery
- Product description
- Product price
- Stock availability
- Quantity selector
- Add to Cart button
- Out-of-stock indication
- Related category information

---

## 4. Product Search & Filtering

### Scope

- Keyword search
- Search by product name
- Category filtering
- Price filtering
- Price sorting
- Basic availability filtering
- Clear/reset filters

#### Example Sorting Options

- Price: Low to High
- Price: High to Low
- Newest
- Featured

---

## 5. Shopping Cart

### Scope

- Add product to cart
- Remove product from cart
- Update product quantity
- View cart contents
- Calculate product subtotal
- Calculate shipping fee
- Calculate discounts
- Calculate order total
- Validate available inventory
- Prevent ordering unavailable products
- Persistent cart for authenticated customers

#### Example Cart Calculation

```text
Product Subtotal     ₱2,000
Shipping               ₱150
Discount               -₱200
----------------------------
Grand Total           ₱1,950
```

---

## 6. Checkout

### Scope

- Customer information
- Customer name
- Email address
- Mobile number
- Shipping address
- Billing address if required
- Shipping method selection
- Payment method selection
- Order notes
- Order summary
- Shipping fee calculation
- Discount calculation
- Grand total calculation
- Place order
- Order confirmation page

#### Checkout Types

The MVP may support:

- Guest checkout
- Registered customer checkout

Guest checkout is recommended to reduce checkout friction.

---

## 7. Payment

The MVP should initially support only the payment methods required by the business.

### Option A — Manual Payments

#### Scope

- Cash on Delivery
- Bank transfer
- Manual payment verification
- Payment reference
- Admin payment status update

### Option B — Online Payment Gateway

Possible payment providers:

- PayMongo
- Xendit
- Stripe

#### Payment Gateway Scope

- Create payment transaction
- Payment checkout flow
- Payment success handling
- Payment failure handling
- Payment cancellation handling
- Payment webhook processing
- Webhook verification
- Payment reference storage
- Payment status tracking
- Link payment transaction to order

#### Payment Statuses

- Pending
- Paid
- Failed
- Cancelled
- Refunded

Multiple payment gateways are not recommended for the initial MVP unless specifically required.

---

## 8. Shipping & Delivery

### Scope

- Customer shipping address
- Shipping method
- Flat-rate shipping
- Free shipping
- Free shipping threshold
- Store pickup
- Shipping fee calculation
- Shipping information attached to order

#### Example Shipping Configuration

```text
Metro Manila       ₱150
Provincial         ₱250
Store Pickup       FREE
Orders > ₱3,000    FREE
```

#### Phase 2 / Optional Integrations

Advanced courier integrations can be implemented separately.

Examples:

- Lalamove
- Grab
- J&T Express
- Ninja Van
- LBC
- Other courier APIs

---

## 9. Order Management

### Scope

- Generate unique order number
- Create customer order
- Store order items
- Store customer information
- Store shipping information
- Store payment information
- Calculate subtotal
- Calculate discounts
- Calculate shipping
- Calculate grand total
- Order confirmation
- Customer order history
- Order details
- Order status management

#### Order Information

Each order should contain:

```text
Order Number
Customer
Contact Details
Shipping Address
Products
Quantity
Unit Price
Subtotal
Discount
Shipping Fee
Grand Total
Payment Method
Payment Status
Order Status
Order Date
Customer Notes
Admin Notes
```

#### Order Statuses

```text
Pending
Confirmed
Processing
Ready for Pickup
Shipped
Completed
Cancelled
```

#### Basic Order Workflow

```text
Pending
   ↓
Confirmed
   ↓
Processing
   ↓
Ready for Pickup / Shipped
   ↓
Completed
```

---

## 10. Customer Account

### Scope

- Customer dashboard
- View profile
- Update profile
- Change password
- Manage shipping addresses
- View order history
- View individual order details
- View payment information
- View order status

For the MVP, customers should generally not be allowed to modify orders that have already entered processing.

---

## 11. Admin Dashboard

### Scope

The admin dashboard provides a basic operational overview of the store.

#### Dashboard Metrics

- Total orders
- Pending orders
- Processing orders
- Completed orders
- Cancelled orders
- Total customers
- Total products
- Total revenue
- Recent orders
- Low-stock products
- Out-of-stock products

Advanced business intelligence and analytics are not required for the MVP.

---

## 12. Product Management

### Scope

Administrators can:

- Create products
- Edit products
- Archive products
- Activate/deactivate products
- Upload product images
- Remove product images
- Manage product name
- Manage product SKU
- Manage description
- Manage pricing
- Assign categories
- Update stock quantity
- Mark products as featured
- Manage product availability

---

## 13. Category Management

### Scope

Administrators can:

- Create categories
- Edit categories
- Delete/archive categories
- Activate/deactivate categories
- Assign products to categories
- View products under categories
- Manage category name
- Manage category slug

---

## 14. Inventory Management

### Scope

- Product stock quantity
- Stock deduction
- Manual stock adjustment
- Out-of-stock detection
- Low-stock warning
- Prevent checkout when inventory is unavailable
- Inventory information visible from admin

#### Basic Inventory Workflow

```text
Order Placed
     ↓
Inventory Validated
     ↓
Stock Deducted
     ↓
Remaining Stock Updated
     ↓
Stock Reaches 0
     ↓
Product Marked Out of Stock
```

#### Inventory Information

```text
Current Stock
Available Stock
Stock Adjustments
Out-of-Stock Status
Low-Stock Status
```

Multi-warehouse inventory is excluded from the MVP.

---

## 15. Admin Order Management

### Scope

Administrators can:

- View orders
- Search orders
- Filter orders
- View customer details
- View ordered products
- View shipping address
- View payment information
- View order totals
- Update order status
- Update payment status
- Add internal notes
- Cancel orders
- Mark manual payments as paid

#### Order Filters

- Order number
- Customer
- Date
- Order status
- Payment status
- Payment method

---

## 16. Customer Management

### Scope

Administrators can:

- View customer list
- Search customers
- View customer profile
- View contact information
- View customer addresses
- View previous orders
- View total customer orders
- View total customer spending
- Activate/deactivate customer accounts

---

## 17. Discounts & Promotions

The MVP should use a simple discount system.

### Scope

- Create discount code
- Percentage discount
- Fixed amount discount
- Minimum purchase requirement
- Start date
- Expiration date
- Active/inactive status
- Validate discount during checkout
- Apply discount to order total

#### Example

```text
Code: WELCOME10

Discount: 10%
Minimum Order: ₱1,000
Expiration: December 31
Status: Active
```

#### Excluded From MVP

- Buy One Get One
- Buy 2 Get 1
- Product bundles
- Complex promotion rules
- Customer segmentation
- Tier-based pricing
- Loyalty rewards

---

## 18. Transactional Notifications

### Scope

Basic transactional email notifications should be available.

#### Customer Notifications

- Order confirmation
- Payment confirmation
- Order processing notification
- Order shipped notification
- Order completed notification
- Order cancelled notification

#### Admin Notifications

Optional basic notifications:

- New order received
- New payment received

SMS and marketing automation are excluded from the initial MVP.

---

## 19. Content Pages

### Scope

Basic informational pages:

- Home
- Shop
- About
- Contact
- Privacy Policy
- Terms & Conditions
- Shipping Policy
- Return/Refund Policy

Content should be manageable without modifying application code where practical.

---

## 20. Responsive Design

### Scope

The customer-facing website should support:

- Desktop
- Laptop
- Tablet
- Mobile

#### Responsive Areas

- Navigation
- Product listing
- Product details
- Shopping cart
- Checkout
- Customer account
- Forms
- Content pages

The admin dashboard should also be usable on common desktop and tablet screen sizes.

---

## 21. Basic SEO

### Scope

- SEO-friendly URLs
- Product slugs
- Category slugs
- Page titles
- Meta descriptions
- Product metadata
- Open Graph metadata
- Canonical URLs where required
- XML sitemap
- Robots.txt
- Search engine indexing configuration

Advanced SEO management is excluded from the initial MVP.

---

## 22. Security & Access Control

### Scope

- Secure authentication
- Password hashing
- CSRF protection
- Input validation
- Server-side authorization
- Customer/admin access separation
- Secure session management
- Protected admin routes
- Rate limiting where necessary
- Payment webhook validation
- Prevent unauthorized order access
- Prevent unauthorized admin access

#### Roles

Minimum roles:

```text
Customer
Admin
```

Additional staff roles and advanced permission management can be introduced later.

---

## 23. Store Settings

### Scope

Administrators can configure basic store information.

#### General Settings

- Store name
- Store logo
- Store email
- Contact number
- Business address
- Currency
- Default shipping fee
- Free shipping threshold
- Tax configuration where applicable
- Store social links

---

## 24. Optional Product Variants

Product variants should only be included in the MVP if required by the type of products being sold.

### Scope

Possible variants:

- Size
- Color
- Style
- Variant SKU
- Variant price
- Variant inventory

#### Example

```text
Product: Classic Shirt

Small / Black
SKU: SHIRT-S-BLK
Price: ₱899
Stock: 10

Medium / Black
SKU: SHIRT-M-BLK
Price: ₱899
Stock: 15
```

---

## MVP Modules Summary

The system can be organized into eight primary modules:

```text
1. Authentication & Customers
2. Product Catalog
3. Shopping Cart
4. Checkout
5. Payments
6. Orders
7. Inventory
8. Administration
```

Supporting modules:

```text
Shipping
Discounts
Notifications
Content Management
SEO
Store Settings
```

---

## MVP Customer Flow

```text
Visit Website
      ↓
Browse / Search Products
      ↓
View Product
      ↓
Add to Cart
      ↓
View Cart
      ↓
Checkout
      ↓
Enter Customer Information
      ↓
Select Shipping
      ↓
Select Payment
      ↓
Place Order
      ↓
Payment
      ↓
Order Confirmation
      ↓
Order Processing
      ↓
Shipping / Pickup
      ↓
Completed
```

---

## MVP Admin Flow

```text
Admin Login
     ↓
Dashboard
     ↓
Manage Products
     ↓
Manage Inventory
     ↓
Receive Orders
     ↓
Verify Payment
     ↓
Process Order
     ↓
Update Order Status
     ↓
Ship / Prepare Pickup
     ↓
Complete Order
```

---

## Features Excluded From MVP

The following features should be considered **Phase 2 features, optional modules, or paid add-ons**.

### Customer Experience

- Wishlist
- Product reviews
- Product ratings
- Loyalty points
- Reward system
- Referral system
- Affiliate system
- Gift cards
- Customer wallet
- Product comparison

### Commerce

- Subscription products
- Membership pricing
- Wholesale pricing
- Tier pricing
- Product bundles
- Advanced promotion engine
- Multi-currency
- Multi-language
- Marketplace / multi-vendor

### Inventory & Operations

- Multi-warehouse inventory
- Warehouse management
- Purchase orders
- Supplier management
- Advanced inventory forecasting
- Barcode management
- POS integration

### Logistics

- Real-time courier API
- Automatic shipping labels
- Live delivery tracking
- Distance-based delivery
- Multiple logistics providers

### Marketing

- Abandoned cart automation
- Email marketing
- SMS marketing
- Marketing campaigns
- Customer segmentation
- Marketing funnels
- Advanced CRM

### AI & Automation

- AI chatbot
- AI product recommendations
- AI search
- Automated customer support
- AI-generated product descriptions
- AI marketing automation

### Integrations

- ERP integration
- Accounting software integration
- CRM integration
- Third-party marketplace synchronization
- External warehouse integration

### Reporting

- Advanced sales analytics
- Customer cohort analysis
- Revenue forecasting
- Product profitability analysis
- Custom report builder
- Business intelligence dashboards

### Applications

- Native Android application
- Native iOS application

---

## Recommended MVP Deliverable

> A production-ready e-commerce website where customers can browse and search products, manage their shopping cart, checkout, make payments, receive order confirmations, and monitor their orders.
>
> Administrators can securely manage products, categories, inventory, customers, discounts, payments, orders, shipping configuration, and basic store settings through an administrative dashboard.

---

## MVP Success Criteria

The MVP is considered operational when:

- Customers can browse products.
- Customers can search and filter products.
- Customers can add products to their cart.
- Customers can complete checkout.
- Customers can select an available payment method.
- Orders can be successfully created.
- Payments can be associated with orders.
- Inventory can be automatically updated.
- Customers can view their order history.
- Administrators can manage products.
- Administrators can manage inventory.
- Administrators can process orders.
- Administrators can manage customers.
- Administrators can configure basic store settings.
- Transactional notifications are successfully delivered.
- The website works correctly on desktop and mobile devices.
- Customer and administrative areas are properly secured.
