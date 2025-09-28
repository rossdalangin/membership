# WordPress Membership Pro - Technical Specification & Architectural Plan

This document outlines the detailed specification and architecture for the WordPress Membership Pro plugin. It is intended for developers, project managers, and stakeholders to understand the plugin's features, implementation details, and overall structure.

## 1. Core Features

This section details the primary features of the plugin, grouped by functionality.

### 1.1. Membership & Subscriptions

- **Multiple Subscription Tiers:** Create unlimited membership levels (e.g., Bronze, Silver, Gold) with different pricing, content access, and features.
- **Recurring Billing:** Flexible billing cycles (daily, weekly, monthly, yearly) for automated subscription renewals.
- **Trial Periods:** Offer free or paid trial periods to attract new members.
- **One-Time Offers (Upsells/Downsells):** Present special offers to users during the registration process to increase average order value.
- **Upgrades/Downgrades:** Allow users to switch between membership tiers, with prorated billing calculations.
- **Bonuses:** Offer one-time bonuses or recurring benefits for specific membership tiers.
- **Coupon Management:** Create and manage discount codes (percentage or flat rate) with usage limits and expiration dates.

### 1.2. Payments & Transactions

- **Multiple Payment Gateways:**
    - **Offline Payments:** Instructions for bank transfers, checks, etc.
    - **GCash:** Integration with the popular Filipino payment gateway.
    - **PayPal:** Standard PayPal checkout and subscriptions.
    - **Stripe:** Stripe Checkout and Elements for credit card processing.
- **Secure Processing:** All transactions are handled securely, with sensitive data managed by the payment gateways to ensure PCI compliance.
- **Refunds:** Process full or partial refunds directly from the WordPress admin dashboard.
- **Invoices:** Automatically generate and send PDF invoices to users upon payment.
- **Webhooks:** Robust webhook handling for payment gateways to ensure subscription statuses are updated in real-time.
- **Retry Logic:** Automated dunning management to retry failed recurring payments before canceling a subscription.

### 1.3. Content & Access Control

- **Role-Based Permissions:** Automatically assign a specific user role upon subscription, granting access to content based on capabilities.
- **Drip Content (Auto-Feeder):** Release content to members on a schedule (e.g., 7 days after registration, on a specific date).
- **Secure File Downloads:** Protect files and offer secure, expiring download links to members.
- **Scheduled Publishing:** Make posts or pages available only to members after a certain date.
- **Content Protection by Plan:** Restrict access to posts, pages, custom post types, categories, or specific parts of content using shortcodes.

### 1.4. Affiliate Management

- **Affiliate Dashboard:** A dedicated area for affiliates to track their performance, view earnings, and access promotional materials.
- **Unique Affiliate Links:** Each affiliate receives a unique referral link to track referred visitors and sales.
- **Commission Tracking:** Set custom commission rates (percentage or flat rate) per plan and track earnings in real-time.
- **Payout Requests:** Affiliates can request payouts once they reach a minimum threshold. Admins can manage and process these requests.
- **Promo Tools:** Provide affiliates with banners, email swipes, and other promotional materials.
- **Referral Statistics:** Detailed analytics for affiliates, including clicks, conversions, and commission history.

### 1.5. Marketing & Growth

- **Lead Capture Forms:** Create simple forms to capture leads for email marketing campaigns.
- **Email Marketing Integrations:** Seamless integration with popular services like Mailchimp, ConvertKit, and ActiveCampaign.
- **Landing Pages:** Simple, customizable landing page templates for membership offers.
- **Upsell/Downsell Funnels:** Create post-purchase funnels to guide users to additional offers.
- **Coupon Campaigns:** Run targeted campaigns with unique, time-sensitive coupon codes.

### 1.6. Analytics & Reporting

- **Membership Statistics:** Dashboard widgets and reports showing total members, active subscriptions, and growth over time.
- **Financial Metrics:** Track Monthly Recurring Revenue (MRR), churn rate, and Lifetime Value (LTV).
- **Affiliate Performance:** Monitor top-performing affiliates and overall program effectiveness.
- **Exportable Reports:** Export data to CSV for custom analysis.
- **Scheduled Emails:** Automatically send daily, weekly, or monthly summary reports to administrators.

### 1.7. User Experience & Admin Tools

- **User Registration & Login:** Customizable registration and login forms.
- **Member Portal:** A front-end dashboard for users to manage their subscription, view billing history, and update their profile.
- **Automated Notifications:** Customizable email templates for events like successful payments, failed payments, subscription cancellations, and upcoming renewals.
- **Customizable Dashboards:** Both admin and user dashboards are modular and can be customized.
- **Admin Moderation Tools:** Manually approve new members, pause subscriptions, or revoke access.

## 2. WordPress-Specific Implementation

This section describes how the plugin will integrate with the WordPress core APIs and conventions.

### 2.1. Custom Post Types (CPTs)

- **`wmp_membership_plan`**:
    - **Purpose**: To store and manage the different membership levels.
    - **Supports**: `title`, `editor` (for description), `custom-fields` (for price, billing cycle, trial period, role assignment, etc.), `thumbnail`.
    - **Meta Fields**:
        - `_wmp_price`: The cost of the plan.
        - `_wmp_billing_period`: e.g., `month`, `year`.
        - `_wmp_billing_frequency`: e.g., `1` (for every 1 month).
        - `_wmp_trial_days`: Number of trial days.
        - `_wmp_assigned_role`: The user role to assign upon subscription.

- **`wmp_payment`**:
    - **Purpose**: To log every transaction attempt.
    - **Supports**: `title` (e.g., "Payment for Subscription #123"), `editor` (for notes), `custom-fields`.
    - **Meta Fields**:
        - `_wmp_user_id`: The ID of the user.
        - `_wmp_subscription_id`: The ID of the related subscription.
        - `_wmp_amount`: The transaction amount.
        - `_wmp_gateway`: e.g., `stripe`, `paypal`.
        - `_wmp_transaction_id`: The ID from the payment gateway.
        - `_wmp_status`: e.g., `completed`, `pending`, `failed`, `refunded`.

### 2.2. Custom Taxonomies

- **`wmp_plan_category`**:
    - **Purpose**: To categorize membership plans (e.g., "Individual", "Business").
    - **Associated CPT**: `wmp_membership_plan`.

### 2.3. User Roles & Capabilities

- The plugin will create new user roles dynamically based on the membership plans created. For a "Gold" plan, a "WMP Gold Member" role could be created.
- **Custom Capabilities**:
    - `access_wmp_content_for_plan_{plan_id}`: A dynamic capability granted to users with an active subscription to a specific plan. This will be the primary mechanism for content restriction.
    - `manage_wmp_settings`: For administrators to manage plugin settings.
    - `view_wmp_reports`: For administrators or managers to view analytics.
    - `is_wmp_affiliate`: A capability assigned to users who are registered as affiliates.

### 2.4. REST API Endpoints

- **Namespace**: `wmp/v1`
- **Endpoints**:
    - `/subscriptions`:
        - `GET`: Retrieve subscriptions for the authenticated user.
        - `POST`: Create a new subscription (checkout process).
    - `/subscriptions/<id>`:
        - `GET`: View a specific subscription.
        - `PUT`: Update a subscription (e.g., upgrade/downgrade).
        - `DELETE`: Cancel a subscription.
    - `/payments`:
        - `GET`: Retrieve payment history for the authenticated user.
    - `/affiliates/stats`:
        - `GET`: Retrieve stats for the authenticated affiliate user.
    - `/downloads/<file_id>`:
        - `GET`: Generate and provide a secure, expiring download link.

### 2.5. Hooks & Filters (for Extensibility)

- **Actions**:
    - `wmp_subscription_created( $subscription_id, $user_id, $plan_id )`: Fires when a new subscription is created.
    - `wmp_subscription_activated( $subscription_id, $user_id )`: Fires when a subscription becomes active (e.g., after successful payment).
    - `wmp_subscription_cancelled( $subscription_id, $user_id )`: Fires when a subscription is canceled.
    - `wmp_payment_processed( $payment_id, $transaction_id, $gateway )`: Fires after a payment is successfully processed.
    - `wmp_payment_failed( $payment_id, $user_id )`: Fires when a payment fails.
- **Filters**:
    - `wmp_get_restriction_message( $message, $post_id )`: Allows customization of the content restriction message.
    - `wmp_redirect_after_login( $redirect_url, $user )`: Allows modification of the redirect URL after a member logs in.
    - `wmp_email_template_args( $args, $email_template_name )`: Allows filtering of arguments passed to email templates.

### 2.6. Shortcodes & Gutenberg Blocks

- **`[wmp_plans]`**: Displays the membership plan pricing grid.
    - **Block**: "Membership Plans Grid"
- **`[wmp_checkout]`**: Renders the checkout form for a specific plan.
    - **Block**: "Membership Checkout"
- **`[wmp_account]`**: Displays the user's account portal (subscription details, billing history, profile).
    - **Block**: "Member Account Portal"
- **`[wmp_affiliate_dashboard]`**: Shows the affiliate dashboard.
    - **Block**: "Affiliate Dashboard"
- **`[wmp_restrict plan_id="1,2"]...[/wmp_restrict]`**: Restricts the enclosed content to members of specific plans.
    - **Block**: "Restricted Content" block with plan selection options.
- **`[wmp_login_form]`**: Displays a customized login form.
    - **Block**: "Member Login Form"

## 3. Advanced / Standout Features

This section outlines features designed to increase user engagement, drive growth, and provide a premium experience.

### 3.1. Gamification

- **Badges & Achievements:** Award members with badges for completing certain actions (e.g., "Pioneer" for being a member for one year, "Engaged" for commenting on 10 posts).
- **Points System:** Members earn points for engagement (e.g., logging in, completing a course, referring a friend), which can be redeemed for discounts or exclusive content.
- **Leaderboards:** Display leaderboards to foster friendly competition among members.

### 3.2. Affiliate Contests

- **Seasonal Leaderboards:** Run time-based affiliate contests (e.g., "Q4 Sales Contest") with dedicated leaderboards and prize tiers.
- **Automated Prize Fulfillment:** Automatically trigger bonuses or higher commission rates for contest winners.

### 3.3. AI-Driven Content Recommendations

- **Personalized Content Suggestions:** Use AI to analyze a member's viewing history and suggest other relevant content (posts, courses, files) they might be interested in.
- **Weekly Digest Emails:** Automatically generate and send personalized weekly digest emails with recommended content.

### 3.4. Community Features

- **Built-in Forums:** Simple, lightweight forums for members to discuss topics and connect with each other.
- **Member Groups:** Allow members to create and join public or private groups based on interests.
- **Private Messaging:** A secure, in-platform messaging system for members to communicate directly.

### 3.5. Mobile App Integration

- **Full REST API Coverage:** The comprehensive REST API allows for the development of a native mobile app (iOS/Android) that can manage subscriptions, access content, and interact with community features.
- **Push Notifications:** Send push notifications to the mobile app for important events (e.g., new content released, payment reminders).

## 4. Developer & Architecture Guidelines

This section provides technical guidelines for building, securing, and scaling the plugin.

### 4.1. Plugin Folder Structure

A modular, maintainable folder structure will be used:

```
/wordpress-membership-pro
|-- /admin/                 # Admin-specific functionality (menus, settings pages)
|-- /api/                   # REST API endpoint definitions
|-- /affiliates/            # Affiliate program module
|-- /core/                  # Core logic (CPTs, roles, capabilities, main plugin class)
|-- /cron/                  # Scheduled tasks (dunning, content dripping)
|-- /gateways/              # Payment gateway integrations (Stripe, PayPal, etc.)
|-- /includes/              # Shared libraries, activation/deactivation hooks, loader
|-- /integrations/          # Third-party integrations (Mailchimp, etc.)
|-- /languages/             # .pot, .po, .mo files for translation
|-- /public/                # Public-facing functionality (shortcodes, content protection)
|-- /templates/             # Overridable template files for front-end views
|-- wordpress-membership-pro.php # Main plugin file
```

### 4.2. Database Schema

Custom tables will be used for high-volume, non-post data to ensure performance and avoid bloating the `wp_posts` and `wp_postmeta` tables.

- **`wp_wmp_subscriptions`**
    - `id` (bigint, PK, auto_increment)
    - `user_id` (bigint, index)
    - `plan_id` (bigint, index) - Relates to a `wmp_membership_plan` CPT ID.
    - `status` (varchar) - e.g., `active`, `pending`, `expired`, `cancelled`.
    - `start_date` (datetime)
    - `end_date` (datetime, nullable) - For fixed-term or cancelled subscriptions.
    - `trial_end` (datetime, nullable)
    - `gateway` (varchar) - e.g., `stripe`, `paypal`.
    - `gateway_subscription_id` (varchar, index) - The subscription ID from the payment gateway.
    - `created_at` (datetime)
    - `updated_at` (datetime)

- **`wp_wmp_transactions`**
    - `id` (bigint, PK, auto_increment)
    - `subscription_id` (bigint, index) - Relates to `wp_wmp_subscriptions.id`.
    - `user_id` (bigint, index)
    - `amount` (decimal)
    - `gateway` (varchar)
    - `transaction_id` (varchar, index) - The charge/payment ID from the gateway.
    - `status` (varchar) - e.g., `completed`, `pending`, `failed`, `refunded`.
    - `created_at` (datetime)

- **`wp_wmp_affiliates`**
    - `id` (bigint, PK, auto_increment)
    - `user_id` (bigint, unique index) - The WordPress user ID.
    - `status` (varchar) - e.g., `active`, `pending`, `rejected`.
    - `commission_rate` (decimal) - Affiliate-specific commission rate.
    - `created_at` (datetime)

- **`wp_wmp_referrals`**
    - `id` (bigint, PK, auto_increment)
    - `affiliate_id` (bigint, index) - Relates to `wp_wmp_affiliates.id`.
    - `referring_url` (text)
    - `ip_address` (varchar)
    - `transaction_id` (bigint, index, nullable) - Relates to `wp_wmp_transactions.id` upon conversion.
    - `status` (varchar) - e.g., `pending`, `converted`, `rejected`.
    - `created_at` (datetime)

### 4.3. Security Best Practices

- **PCI Compliance:** Payment gateways will handle all sensitive credit card data. No card details will be stored on the server.
- **Data Escaping & Sanitization:** All user input will be sanitized, and all output will be escaped (`esc_html`, `esc_attr`, etc.).
- **Nonce Checks:** Use nonces for all form submissions and admin actions to prevent CSRF attacks.
- **GDPR Tools:**
    - **Data Export:** Integrate with WordPress's data export tool to include subscription and transaction history.
    - **Data Erasure:** Hook into the data erasure tool to anonymize or delete user data from custom tables.
- **Secure File Downloads:** Files will be stored outside the public root or protected by `.htaccess`. A PHP script will serve files after verifying permissions, and links will be time-limited.

### 4.4. Performance & Scaling Strategies

- **Object Caching:** Use the WordPress Object Cache (`wp_cache_set`, `wp_cache_get`) for frequently accessed data that doesn't change often.
- **Transients API:** Use transients for expensive queries, such as reports and statistics.
- **Database Indexing:** All custom tables will have appropriate indexes on columns used in `WHERE` clauses (e.g., `user_id`, `subscription_id`).
- **CDN for Files:** Recommend and integrate with CDNs for serving secure file downloads to reduce server load.
- **Autoloading Options:** Be selective about which options are autoloaded to avoid slowing down every page load.

### 4.5. Testing & Migration

- **Unit & Integration Tests:** Use PHPUnit for testing individual functions (unit tests) and their interactions (integration tests).
- **Database Versioning:** Use a database versioning system (e.g., an option in `wp_options`) to manage schema changes across plugin updates. The activation hook will handle initial creation, and subsequent updates will be handled by an admin hook that checks the DB version.
- **Migration Processes:** Provide clear documentation and, where possible, automated scripts for migrating from popular existing membership plugins.

## 5. Deliverables & Examples

This section summarizes the final deliverables and provides practical examples for developers.

### 5.1. Summary of Deliverables

- **WordPress Membership Pro Plugin:** A premium, modular, and extensible membership plugin.
- **Comprehensive Documentation:**
    - **User Guide:** For site administrators, explaining how to configure and manage the plugin.
    - **Developer Guide:** For developers, detailing all hooks, filters, REST API endpoints, and template overriding.
- **Testing Suite:** A full suite of unit and integration tests to ensure code quality and stability.

### 5.2. Example: Using an Action Hook

A developer wants to send a custom welcome gift to a user when their subscription becomes active. They can use the `wmp_subscription_activated` action.

```php
add_action( 'wmp_subscription_activated', 'send_welcome_gift_on_activation', 10, 2 );

function send_welcome_gift_on_activation( $subscription_id, $user_id ) {
    $user_info = get_userdata( $user_id );
    // Code to connect to a fulfillment service API
    // fulfillment_service_api_request( 'send_gift', $user_info->user_email );
}
```

### 5.3. Example: Using a Filter

A developer wants to change the default content restriction message to include a link to their sales page. They can use the `wmp_get_restriction_message` filter.

```php
add_filter( 'wmp_get_restriction_message', 'customize_restriction_message', 10, 2 );

function customize_restriction_message( $message, $post_id ) {
    $custom_message = '<h3>Content Locked!</h3>';
    $custom_message .= '<p>This content is exclusive to our members. <a href="/pricing/">Upgrade your account</a> to get access!</p>';
    return $custom_message;
}
```

### 5.4. Example: Using the REST API

A developer is building a custom dashboard outside of WordPress and needs to fetch the current user's subscription details. They can make a `GET` request to the `/wmp/v1/subscriptions` endpoint.

**Request:**

```bash
curl -X GET "https://yourdomain.com/wp-json/wmp/v1/subscriptions" \
-H "Authorization: Bearer <YOUR_JWT_OR_AUTH_TOKEN>"
```

**Example JSON Response:**

```json
[
  {
    "id": 42,
    "plan_id": 3,
    "plan_name": "Gold Membership",
    "status": "active",
    "start_date": "2023-10-27T10:00:00",
    "end_date": null,
    "links": {
      "self": [
        {
          "href": "https://yourdomain.com/wp-json/wmp/v1/subscriptions/42"
        }
      ],
      "collection": [
        {
          "href": "https://yourdomain.com/wp-json/wmp/v1/subscriptions"
        }
      ]
    }
  }
]
```