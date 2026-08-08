<?php

namespace Database\Seeders;

use App\Enums\LandingPageTheme;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();

        $categories = $this->seedCategories();

        $this->seedProducts($categories);
        $this->seedDiscounts();
        $this->seedStoreSettings();
        $this->seedPages();
    }

    private function seedUsers(): void
    {
        $users = [
            [
                'name' => 'Up Shop Admin',
                'email' => 'admin@example.com',
                'phone' => '+63 917 555 0101',
                'is_admin' => true,
            ],
            [
                'name' => 'Jamie Santos',
                'email' => 'customer@example.com',
                'phone' => '+63 917 555 0102',
                'is_admin' => false,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                [
                    'email' => $userData['email'],
                ],
                [
                    'name' => $userData['name'],
                    'phone' => $userData['phone'],
                    'password' => 'password',
                    'is_admin' => $userData['is_admin'],
                    'is_active' => true,
                ],
            );

            /*
             * email_verified_at is intentionally force-filled because it is
             * not included in the User model's mass-assignable attributes.
             * Development accounts must be verified so the local admin can
             * access Filament immediately.
             */
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $categories = [
            [
                'name' => 'Apparel',
                'slug' => 'apparel',
                'description' => 'Everyday wardrobe essentials, relaxed tailoring, versatile layers, and modern clothing designed for repeat wear.',
            ],
            [
                'name' => 'Footwear',
                'slug' => 'footwear',
                'description' => 'Sneakers, loafers, sandals, and casual footwear selected for comfort, durability, and everyday styling.',
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'description' => 'Bags, watches, belts, eyewear, and practical finishing pieces designed to complement an everyday wardrobe.',
            ],
            [
                'name' => 'Beauty & Self-Care',
                'slug' => 'beauty-self-care',
                'description' => 'Simple skincare, body care, sun protection, and personal fragrance for practical daily routines.',
            ],
            [
                'name' => 'Home & Lifestyle',
                'slug' => 'lifestyle',
                'description' => 'Thoughtful home, coffee, desk, and everyday lifestyle essentials made for comfortable modern spaces.',
            ],
        ];

        $seededCategories = [];

        foreach ($categories as $categoryData) {
            $category = Category::query()
                ->withTrashed()
                ->updateOrCreate(
                    [
                        'slug' => $categoryData['slug'],
                    ],
                    [
                        'name' => $categoryData['name'],
                        'description' => $categoryData['description'],
                        'is_active' => true,
                    ],
                );

            if ($category->trashed()) {
                $category->restore();
            }

            $seededCategories[$categoryData['slug']] = $category;
        }

        return $seededCategories;
    }

    /**
     * @param  array<string, Category>  $categories
     */
    private function seedProducts(array $categories): void
    {
        $products = [
            [
                'category_slug' => 'apparel',
                'name' => 'Linen Blend Overshirt',
                'slug' => 'linen-blend-overshirt',
                'sku' => 'UPS-APP-001',
                'description' => 'A lightweight linen-blend overshirt with a relaxed silhouette, clean patch pockets, and an easy layering weight for warm days and cool evenings.',
                'price' => 189_000,
                'stock_quantity' => 18,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'Linen Blend Overshirt | Up Shop',
                'meta_description' => 'Shop the Linen Blend Overshirt, a lightweight everyday layer with a relaxed fit and versatile styling.',
            ],
            [
                'category_slug' => 'apparel',
                'name' => 'Essential Heavyweight Tee',
                'slug' => 'essential-heavyweight-tee',
                'sku' => 'UPS-APP-002',
                'description' => 'A structured cotton crew-neck T-shirt with a substantial hand feel, relaxed proportions, and reinforced seams for dependable everyday wear.',
                'price' => 79_000,
                'stock_quantity' => 42,
                'low_stock_threshold' => 8,
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'Essential Heavyweight Tee | Up Shop',
                'meta_description' => 'A premium heavyweight cotton T-shirt with a relaxed fit and durable everyday construction.',
            ],
            [
                'category_slug' => 'apparel',
                'name' => 'Pleated Wide-Leg Trousers',
                'slug' => 'pleated-wide-leg-trousers',
                'sku' => 'UPS-APP-003',
                'description' => 'Relaxed wide-leg trousers with front pleats, side pockets, and a clean drape that works equally well with shirts, tees, and lightweight knitwear.',
                'price' => 159_000,
                'stock_quantity' => 12,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Pleated Wide-Leg Trousers | Up Shop',
                'meta_description' => 'Relaxed pleated trousers with a wide-leg silhouette and versatile everyday styling.',
            ],
            [
                'category_slug' => 'apparel',
                'name' => 'Relaxed Oxford Shirt',
                'slug' => 'relaxed-oxford-shirt',
                'sku' => 'UPS-APP-004',
                'description' => 'A soft cotton Oxford shirt cut with an easy fit and finished with a button-down collar, chest pocket, and curved hem.',
                'price' => 129_000,
                'stock_quantity' => 4,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Relaxed Oxford Shirt | Up Shop',
                'meta_description' => 'A relaxed cotton Oxford shirt designed for polished yet comfortable everyday wear.',
            ],
            [
                'category_slug' => 'apparel',
                'name' => 'Lightweight Utility Jacket',
                'slug' => 'lightweight-utility-jacket',
                'sku' => 'UPS-APP-005',
                'description' => 'A versatile utility jacket with a lightweight woven shell, practical storage pockets, adjustable cuffs, and a clean everyday profile.',
                'price' => 249_000,
                'stock_quantity' => 9,
                'low_stock_threshold' => 4,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Lightweight Utility Jacket | Up Shop',
                'meta_description' => 'A lightweight utility jacket with practical pockets and an easy transitional-weather fit.',
            ],

            [
                'category_slug' => 'footwear',
                'name' => 'Court Classic Sneakers',
                'slug' => 'court-classic-sneakers',
                'sku' => 'UPS-FOT-001',
                'description' => 'Minimal low-top sneakers with a clean court-inspired profile, cushioned footbed, and durable rubber outsole for everyday wear.',
                'price' => 269_000,
                'stock_quantity' => 16,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'Court Classic Sneakers | Up Shop',
                'meta_description' => 'Clean low-top court sneakers with cushioned comfort and versatile everyday styling.',
            ],
            [
                'category_slug' => 'footwear',
                'name' => 'Suede Weekend Loafers',
                'slug' => 'suede-weekend-loafers',
                'sku' => 'UPS-FOT-002',
                'description' => 'Soft suede loafers with a flexible sole and understated shape, designed for smart-casual dressing without sacrificing comfort.',
                'price' => 329_000,
                'stock_quantity' => 8,
                'low_stock_threshold' => 4,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Suede Weekend Loafers | Up Shop',
                'meta_description' => 'Soft suede loafers with flexible comfort for polished weekend and smart-casual looks.',
            ],
            [
                'category_slug' => 'footwear',
                'name' => 'Trail Lite Runners',
                'slug' => 'trail-lite-runners',
                'sku' => 'UPS-FOT-003',
                'description' => 'Lightweight everyday runners featuring breathable mesh panels, supportive cushioning, and a grippy outsole suited to city walks and light trails.',
                'price' => 299_000,
                'stock_quantity' => 21,
                'low_stock_threshold' => 6,
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'Trail Lite Runners | Up Shop',
                'meta_description' => 'Lightweight cushioned runners with breathable construction and dependable everyday traction.',
            ],
            [
                'category_slug' => 'footwear',
                'name' => 'Leather Strap Sandals',
                'slug' => 'leather-strap-sandals',
                'sku' => 'UPS-FOT-004',
                'description' => 'Easy warm-weather sandals with wide leather straps, a contoured footbed, and an adjustable buckle for a secure everyday fit.',
                'price' => 179_000,
                'stock_quantity' => 3,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Leather Strap Sandals | Up Shop',
                'meta_description' => 'Comfortable leather strap sandals with an adjustable fit and contoured everyday footbed.',
            ],
            [
                'category_slug' => 'footwear',
                'name' => 'Canvas Slip-On',
                'slug' => 'canvas-slip-on',
                'sku' => 'UPS-FOT-005',
                'description' => 'A simple canvas slip-on with elastic side panels, padded collar, and vulcanized rubber sole for effortless casual wear.',
                'price' => 149_000,
                'stock_quantity' => 0,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Canvas Slip-On | Up Shop',
                'meta_description' => 'A casual canvas slip-on with easy entry, padded comfort, and a durable rubber outsole.',
            ],

            [
                'category_slug' => 'accessories',
                'name' => 'Structured Mini Tote',
                'slug' => 'structured-mini-tote',
                'sku' => 'UPS-ACC-001',
                'description' => 'A compact structured tote with dual handles, removable shoulder strap, internal pocket, and enough room for daily essentials.',
                'price' => 219_000,
                'stock_quantity' => 11,
                'low_stock_threshold' => 4,
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'Structured Mini Tote | Up Shop',
                'meta_description' => 'A compact structured tote with removable strap and practical organization for everyday essentials.',
            ],
            [
                'category_slug' => 'accessories',
                'name' => 'Everyday Crossbody Bag',
                'slug' => 'everyday-crossbody-bag',
                'sku' => 'UPS-ACC-002',
                'description' => 'A lightweight crossbody with an adjustable strap, secure zip closure, and organized interior designed for hands-free daily use.',
                'price' => 169_000,
                'stock_quantity' => 24,
                'low_stock_threshold' => 6,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Everyday Crossbody Bag | Up Shop',
                'meta_description' => 'A lightweight everyday crossbody bag with adjustable strap and organized interior storage.',
            ],
            [
                'category_slug' => 'accessories',
                'name' => 'Classic Leather Belt',
                'slug' => 'classic-leather-belt',
                'sku' => 'UPS-ACC-003',
                'description' => 'A clean leather belt finished with a brushed metal buckle and subtle edge stitching for dependable everyday styling.',
                'price' => 99_000,
                'stock_quantity' => 35,
                'low_stock_threshold' => 8,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Classic Leather Belt | Up Shop',
                'meta_description' => 'A versatile leather belt with a brushed buckle and timeless everyday construction.',
            ],
            [
                'category_slug' => 'accessories',
                'name' => 'Minimal Steel Watch',
                'slug' => 'minimal-steel-watch',
                'sku' => 'UPS-ACC-004',
                'description' => 'A refined stainless-steel watch with a clean dial, slim case, mineral crystal, and adjustable bracelet for an understated finish.',
                'price' => 389_000,
                'stock_quantity' => 6,
                'low_stock_threshold' => 4,
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'Minimal Steel Watch | Up Shop',
                'meta_description' => 'A minimalist stainless-steel watch with a clean dial and understated everyday design.',
            ],
            [
                'category_slug' => 'accessories',
                'name' => 'Polarized Square Sunglasses',
                'slug' => 'polarized-square-sunglasses',
                'sku' => 'UPS-ACC-005',
                'description' => 'Modern square-frame sunglasses with polarized lenses, lightweight temples, and UV protection for bright everyday conditions.',
                'price' => 129_000,
                'stock_quantity' => 18,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Polarized Square Sunglasses | Up Shop',
                'meta_description' => 'Modern square sunglasses with polarized lenses and comfortable lightweight frames.',
            ],

            [
                'category_slug' => 'beauty-self-care',
                'name' => 'Daily Hydration Cleanser',
                'slug' => 'daily-hydration-cleanser',
                'sku' => 'UPS-BEA-001',
                'description' => 'A gentle daily facial cleanser formulated to remove sunscreen, excess oil, and daily buildup without leaving skin feeling stripped.',
                'price' => 65_000,
                'stock_quantity' => 30,
                'low_stock_threshold' => 8,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Daily Hydration Cleanser | Up Shop',
                'meta_description' => 'A gentle daily facial cleanser designed to remove buildup while maintaining comfortable hydration.',
            ],
            [
                'category_slug' => 'beauty-self-care',
                'name' => 'Barrier Repair Moisturizer',
                'slug' => 'barrier-repair-moisturizer',
                'sku' => 'UPS-BEA-002',
                'description' => 'A lightweight moisturizer with a comfortable cream texture designed to support the skin barrier and provide lasting everyday hydration.',
                'price' => 89_000,
                'stock_quantity' => 22,
                'low_stock_threshold' => 6,
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'Barrier Repair Moisturizer | Up Shop',
                'meta_description' => 'A lightweight daily moisturizer formulated for lasting hydration and skin-barrier support.',
            ],
            [
                'category_slug' => 'beauty-self-care',
                'name' => 'SPF 50 Daily Fluid',
                'slug' => 'spf-50-daily-fluid',
                'sku' => 'UPS-BEA-003',
                'description' => 'A lightweight broad-spectrum SPF 50 facial fluid with a comfortable finish designed for daily wear under makeup or on its own.',
                'price' => 75_000,
                'stock_quantity' => 25,
                'low_stock_threshold' => 7,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'SPF 50 Daily Fluid | Up Shop',
                'meta_description' => 'Lightweight broad-spectrum SPF 50 facial protection designed for comfortable everyday wear.',
            ],
            [
                'category_slug' => 'beauty-self-care',
                'name' => 'Botanical Hand & Body Wash',
                'slug' => 'botanical-hand-body-wash',
                'sku' => 'UPS-BEA-004',
                'description' => 'A refreshing hand and body cleanser with a soft botanical scent and gentle lather suitable for everyday sink or shower use.',
                'price' => 69_000,
                'stock_quantity' => 9,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Botanical Hand & Body Wash | Up Shop',
                'meta_description' => 'A gentle botanical hand and body wash with a refreshing everyday scent.',
            ],
            [
                'category_slug' => 'beauty-self-care',
                'name' => 'Cedar & Citrus Eau de Parfum',
                'slug' => 'cedar-citrus-eau-de-parfum',
                'sku' => 'UPS-BEA-005',
                'description' => 'A balanced 50 ml eau de parfum pairing bright citrus opening notes with dry cedar, soft woods, and a clean musky finish.',
                'price' => 259_000,
                'stock_quantity' => 5,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Cedar & Citrus Eau de Parfum | Up Shop',
                'meta_description' => 'A balanced 50 ml fragrance combining bright citrus, cedar, soft woods, and clean musk.',
            ],

            [
                'category_slug' => 'lifestyle',
                'name' => 'Ceramic Pour-Over Set',
                'slug' => 'ceramic-pour-over-set',
                'sku' => 'UPS-LIF-001',
                'description' => 'A practical ceramic coffee set with a reusable dripper and matching server designed for simple, consistent pour-over brewing at home.',
                'price' => 149_000,
                'stock_quantity' => 14,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => true,
                'meta_title' => 'Ceramic Pour-Over Set | Up Shop',
                'meta_description' => 'A ceramic pour-over coffee set with reusable dripper and matching server for everyday home brewing.',
            ],
            [
                'category_slug' => 'lifestyle',
                'name' => 'Insulated Travel Tumbler',
                'slug' => 'insulated-travel-tumbler',
                'sku' => 'UPS-LIF-002',
                'description' => 'A double-wall insulated tumbler with a secure lid and comfortable grip designed to keep coffee, tea, and cold drinks at temperature longer.',
                'price' => 89_000,
                'stock_quantity' => 27,
                'low_stock_threshold' => 6,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Insulated Travel Tumbler | Up Shop',
                'meta_description' => 'A double-wall insulated travel tumbler made for hot coffee, tea, and everyday cold drinks.',
            ],
            [
                'category_slug' => 'lifestyle',
                'name' => 'Cotton Throw Blanket',
                'slug' => 'cotton-throw-blanket',
                'sku' => 'UPS-LIF-003',
                'description' => 'A soft woven cotton throw with a subtle textured finish, sized for couches, reading chairs, and an extra lightweight layer on the bed.',
                'price' => 129_000,
                'stock_quantity' => 7,
                'low_stock_threshold' => 4,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Cotton Throw Blanket | Up Shop',
                'meta_description' => 'A soft textured cotton throw designed for couches, chairs, and lightweight bedroom layering.',
            ],
            [
                'category_slug' => 'lifestyle',
                'name' => 'Amber Glass Candle',
                'slug' => 'amber-glass-candle',
                'sku' => 'UPS-LIF-004',
                'description' => 'A clean-burning scented candle in an amber glass vessel with warm cedar, soft spice, and subtle citrus notes.',
                'price' => 79_000,
                'stock_quantity' => 2,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => false,
                'meta_title' => 'Amber Glass Candle | Up Shop',
                'meta_description' => 'An amber glass scented candle with warm cedar, soft spice, and subtle citrus notes.',
            ],
            [
                'category_slug' => 'lifestyle',
                'name' => 'Compact Desk Organizer',
                'slug' => 'compact-desk-organizer',
                'sku' => 'UPS-LIF-005',
                'description' => 'A compact desktop organizer with divided storage for notebooks, pens, charging cables, and smaller everyday work essentials.',
                'price' => 99_000,
                'stock_quantity' => 12,
                'low_stock_threshold' => 4,
                'is_active' => false,
                'is_featured' => false,
                'meta_title' => 'Compact Desk Organizer | Up Shop',
                'meta_description' => 'A compact divided desktop organizer for stationery, cables, and everyday workspace essentials.',
            ],
        ];

        foreach ($products as $productData) {
            $category = $categories[$productData['category_slug']];

            $product = Product::query()
                ->withTrashed()
                ->updateOrCreate(
                    [
                        'sku' => $productData['sku'],
                    ],
                    [
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                        'slug' => $productData['slug'],
                        'description' => $productData['description'],
                        'price' => $productData['price'],
                        'stock_quantity' => $productData['stock_quantity'],
                        'low_stock_threshold' => $productData['low_stock_threshold'],
                        'is_active' => $productData['is_active'],
                        'is_featured' => $productData['is_featured'],
                        'meta_title' => $productData['meta_title'],
                        'meta_description' => $productData['meta_description'],
                    ],
                );

            if ($product->trashed()) {
                $product->restore();
            }
        }
    }

    private function seedDiscounts(): void
    {
        $discounts = [
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10,
                'minimum_purchase' => 100_000,
                'starts_at' => now()->startOfDay(),
                'expires_at' => now()->addYear()->endOfDay(),
                'is_active' => true,
            ],
            [
                'code' => 'SAVE250',
                'type' => 'fixed',
                'value' => 25_000,
                'minimum_purchase' => 250_000,
                'starts_at' => now()->startOfDay(),
                'expires_at' => now()->addMonths(6)->endOfDay(),
                'is_active' => true,
            ],
        ];

        foreach ($discounts as $discount) {
            Discount::query()->updateOrCreate(
                [
                    'code' => $discount['code'],
                ],
                $discount,
            );
        }
    }

    private function seedStoreSettings(): void
    {
        $settings = StoreSetting::query()->first()
            ?? new StoreSetting;

        $settings->fill([
            'store_name' => 'Up Shop',
            'store_email' => 'hello@upshop.test',
            'contact_number' => '+63 917 555 0148',
            'business_address' => 'Commerce Center, Taguig City, Metro Manila, Philippines',
            'currency' => 'PHP',
            'default_shipping_fee' => 15_000,
            'free_shipping_threshold' => 300_000,
            'tax_rate_basis_points' => null,
            'social_links' => [],
            'landing_page_theme' => LandingPageTheme::FashionEditorial->value,
        ]);

        $settings->save();
    }

    private function seedPages(): void
    {
        $pages = [
            [
                'title' => 'About',
                'slug' => 'about',
                'content' => <<<'TEXT'
Up Shop is a modern online store focused on useful, well-designed products for everyday life.

Our catalog brings together clothing, footwear, accessories, personal care, and home essentials selected for practical use, straightforward styling, and dependable value.

We believe online shopping should be simple. That means clear product information, transparent pricing, secure checkout, practical delivery options, and customer support when you need it.

Up Shop is based in Metro Manila, Philippines and serves customers through our online storefront.
TEXT,
                'meta_title' => 'About Up Shop',
                'meta_description' => 'Learn about Up Shop, our approach to everyday products, and our commitment to a simple and dependable online shopping experience.',
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'content' => <<<'TEXT'
Need help with a product, existing order, delivery, payment, or your customer account? Our support team is available to assist.

Email
hello@upshop.test

Phone
+63 917 555 0148

Customer Support Hours
Monday to Friday
9:00 AM to 6:00 PM Philippine Time

When contacting us about an existing purchase, please include your order number and the email address used during checkout so we can assist you more efficiently.
TEXT,
                'meta_title' => 'Contact Up Shop',
                'meta_description' => 'Contact Up Shop for help with products, orders, delivery, payments, returns, or your customer account.',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => <<<'TEXT'
Up Shop respects your privacy and collects only the information reasonably required to operate the store, process purchases, provide customer support, and maintain account security.

Information We Collect

We may collect information you provide when creating an account, placing an order, contacting support, or managing your profile. This can include your name, email address, mobile number, billing or shipping address, order information, and payment-related references.

How We Use Information

Information may be used to process orders, arrange delivery or pickup, communicate order updates, respond to customer inquiries, prevent fraud or unauthorized access, and improve store operations.

Payment Information

Up Shop does not intentionally store sensitive payment credentials that are not required by the selected payment method. Manual payment references may be stored where necessary to verify a transaction.

Information Sharing

Customer information may be shared only when reasonably necessary to complete an order, comply with legal obligations, protect the store or its customers, or work with service providers involved in operating the website and fulfilling purchases.

Data Security

Reasonable administrative and technical measures are used to protect customer information. No method of online storage or transmission can guarantee absolute security.

Your Information

Registered customers can review and update basic account information through their customer account. You may also contact us regarding privacy-related questions or requests.

Contact

Privacy questions may be sent to hello@upshop.test.
TEXT,
                'meta_title' => 'Privacy Policy | Up Shop',
                'meta_description' => 'Read how Up Shop collects, uses, protects, and manages customer information.',
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-and-conditions',
                'content' => <<<'TEXT'
These terms govern purchases and use of the Up Shop website.

Products and Availability

We make reasonable efforts to keep product information, pricing, and stock availability accurate. Availability may change before checkout is completed.

Pricing

Prices are displayed in Philippine pesos unless otherwise indicated. The final amount shown during checkout includes applicable discounts, shipping fees, and other configured charges.

Orders

Submitting an order does not guarantee acceptance when an item becomes unavailable, payment cannot be verified, customer information is invalid, or the order cannot reasonably be fulfilled.

Payments

Available payment methods are displayed during checkout. Bank-transfer orders may require manual payment verification before processing.

Shipping and Pickup

Shipping fees, free-shipping eligibility, and store-pickup options are displayed during checkout when available. Delivery estimates are provided as guidance and may be affected by courier or operational delays.

Cancellations

Orders may be cancelled when permitted by the current order status. Orders that have already entered fulfillment or shipping may no longer be eligible for cancellation.

Returns and Refunds

Eligible return and refund requests are handled according to the Return / Refund Policy published on this website.

Account Security

Customers are responsible for maintaining the confidentiality of their account credentials and should notify Up Shop if they believe unauthorized access has occurred.

Changes

These terms may be updated when store policies or operational requirements change.
TEXT,
                'meta_title' => 'Terms & Conditions | Up Shop',
                'meta_description' => 'Review the terms governing orders, payments, shipping, cancellations, accounts, and use of the Up Shop website.',
            ],
            [
                'title' => 'Shipping Policy',
                'slug' => 'shipping-policy',
                'content' => <<<'TEXT'
Up Shop currently supports standard delivery, free-shipping eligibility, and store pickup where available.

Standard Shipping

The default standard shipping fee is ₱150 per order unless a different amount is displayed during checkout.

Free Shipping

Orders with an eligible merchandise subtotal of at least ₱3,000 receive free standard shipping unless otherwise indicated.

Store Pickup

Store pickup is free when it is available and selected during checkout. Customers should wait for confirmation that the order is ready before travelling to the pickup location.

Processing

Orders normally begin processing after they are successfully placed and any required payment verification is completed.

Delivery Information

Customers are responsible for providing a complete and accurate delivery address and contact number. Incorrect or incomplete information can delay delivery.

Delivery Times

Delivery estimates may vary by destination, order volume, holidays, weather, courier operations, and other circumstances outside the store's control.

Questions about an existing shipment can be sent to hello@upshop.test with the corresponding order number.
TEXT,
                'meta_title' => 'Shipping Policy | Up Shop',
                'meta_description' => 'Review Up Shop shipping fees, free-shipping eligibility, store pickup, processing, and delivery information.',
            ],
            [
                'title' => 'Return / Refund Policy',
                'slug' => 'return-refund-policy',
                'content' => <<<'TEXT'
Up Shop wants customers to receive items in the condition and quantity described in their order.

Return Requests

If an item arrives damaged, defective, incorrect, or materially different from the order, contact us as soon as reasonably possible and include the order number, a description of the issue, and supporting photos when applicable.

Eligible Items

Items approved for return should generally remain unused, complete, and in their original condition and packaging unless the return is related to a confirmed product defect.

Non-Returnable Items

For hygiene and safety reasons, opened personal-care products may not be eligible for return unless they were delivered damaged, defective, or incorrect.

Refunds

Approved refunds are processed after the returned item or supporting evidence has been reviewed. The refund method and processing period may depend on the original payment method.

Shipping Costs

Return-shipping responsibility will depend on the reason for the return. Where Up Shop confirms that an incorrect or defective item was supplied, reasonable return-shipping costs may be covered by the store.

Contact

To request assistance, email hello@upshop.test and include your order number and relevant details.
TEXT,
                'meta_title' => 'Return & Refund Policy | Up Shop',
                'meta_description' => 'Review Up Shop guidelines for returns, damaged or incorrect products, refunds, and return-shipping responsibilities.',
            ],
        ];

        foreach ($pages as $pageData) {
            $page = Page::query()
                ->withTrashed()
                ->updateOrCreate(
                    [
                        'slug' => $pageData['slug'],
                    ],
                    [
                        'title' => $pageData['title'],
                        'content' => $pageData['content'],
                        'meta_title' => $pageData['meta_title'],
                        'meta_description' => $pageData['meta_description'],
                        'is_published' => true,
                    ],
                );

            if ($page->trashed()) {
                $page->restore();
            }
        }
    }
}
