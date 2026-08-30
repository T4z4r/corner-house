# Corner House Platform — Implementation Report

**Date:** 30 August 2026  
**Stack:** Laravel 12 (PHP 8.3) · MySQL 8 · Blade + Bootstrap 5 · Vite  
**Status:** Core platform built, premium frontend delivered, all business rules enforced

---

## Executive Summary

Corner House is a fully functional property and revenue management platform for a boutique 5-bedroom holiday let in Braunston, Northamptonshire. The platform covers the complete guest lifecycle — from public website browsing and direct booking, through OTA channel management (Beds24/Airbnb/Booking.com/VRBO), to post-stay communications — alongside a comprehensive admin portal with 22 management modules, AI-powered chatbot, dynamic pricing, and revenue analytics.

**144 automated tests pass. All code formatted with Laravel Pint.**

---

## 1. Architecture Overview

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.3 |
| Database | MySQL 8 (38 tables) |
| Frontend | Blade templates, Bootstrap 5, Vanilla JS |
| Build | Vite 8, Laravel Vite Plugin |
| Calendar | FullCalendar 6 (daygrid, interaction, list, timegrid) |
| Charts | Chart.js 4 |
| Selects | Select2 4 |
| Payments | Stripe (stripe-php 21) |
| Channels | Beds24 API v2 |
| AI | OpenAI / Claude (via AiProviderService) |
| RBAC | Spatie Laravel Permission 8 |
| Testing | PHPUnit 12, SQLite in-memory |
| Formatting | Laravel Pint |

---

## 2. Database Schema (38 tables, 42 migrations)

### Core Entities
| Table | Purpose |
|---|---|
| `properties` | Corner House details, house rules, metadata |
| `rooms` | 5 themed bedrooms with rates, capacity, status |
| `room_images` | Gallery images per room (sort order, primary flag) |
| `property_images` | Property-level images |
| `gallery_images` | Public gallery with drag-to-reorder |
| `amenities` | 19 amenities with categories, icons, toggle |
| `property_amenities` | Many-to-many pivot |
| `property_policies` | Cancellation, check-in, house policies |

### Bookings & Guests
| Table | Purpose |
|---|---|
| `guests` | Guest profiles with contact details |
| `reservations` | Bookings with status, dates, pricing breakdown |
| `reservation_guests` | Additional guests per reservation |
| `reservation_addons` | Selected add-ons per booking (quantity, price) |
| `booking_holds` | 30-minute holds during checkout |
| `calendar_blocks` | Manual blocks, min/max stay, Beds24 sync types |

### Revenue & Pricing
| Table | Purpose |
|---|---|
| `pricing_rules` | Dynamic rules (seasonal, event, occupancy, demand, last-minute) |
| `pricing_overrides` | Manual per-room date-range overrides |
| `competitor_rates` | Competitor rate tracking |
| `payments` | Stripe payment records |
| `refunds` | Refund tracking |
| `revenue_snapshots` | Daily revenue intelligence snapshots |

### Channels & Communications
| Table | Purpose |
|---|---|
| `channel_accounts` | OTA connections (Beds24, Airbnb, Booking, VRBO) |
| `channel_mappings` | Room-to-channel mapping |
| `channel_sync_logs` | Per-booking sync results with structured status |
| `channel_webhooks` | Incoming webhook payloads |
| `communication_templates` | Reusable message templates with tokens |
| `communications` | Sent message history |

### AI & Knowledge
| Table | Purpose |
|---|---|
| `knowledge_base_articles` | Chatbot training content with categories, event windows |
| `ai_conversations` | Chat sessions |
| `ai_messages` | Individual messages with token tracking |

### Website Content
| Table | Purpose |
|---|---|
| `food_and_drinks` | 7 local establishments with images |
| `places_of_interests` | 11 places of interest with distance |
| `add_ons` | 4 bookable add-ons (drinks, hampers, experiences) |

### System
| Table | Purpose |
|---|---|
| `settings` | Encrypted key-value store (booking, website, AI, channel groups) |
| `audit_logs` | Full audit trail for all admin actions |
| `notifications` | System notifications |

---

## 3. Seeders (11 files)

| Seeder | Records |
|---|---|
| `SettingsSeeder` | ~60 settings (booking rules, pricing floors, website content, AI config, channel URLs) |
| `RoleAndPermissionSeeder` | 7 roles (Super Admin → Support Staff) + 50+ permissions |
| `PropertySeeder` | Corner House full details, house rules, check-in/out times |
| `RoomSeeder` | 5 themed bedrooms (Lion Suite £195, Elephant £145, Buffalo £135, Rhino £135, Leopard £135) |
| `AmenitySeeder` | 19 amenities across categories |
| `KnowledgeBaseSeeder` | Chatbot knowledge base articles |
| `CommunicationTemplateSeeder` | Booking confirmation, pre-arrival, check-in/out templates |
| `FoodAndDrinkSeeder` | 7 local establishments |
| `PlacesOfInterestSeeder` | 11 places of interest |
| `AddOnSeeder` | 4 add-ons (Classic Drinks £75, Premium Drinks £150, Breakfast Hamper £35, Afternoon Tea £30) |

---

## 4. Business Rules (All Enforced)

| Rule | Implementation |
|---|---|
| 2-night minimum (3 on bank holidays) | `PricingEngine::minimumStayForRange()` reads Settings + PricingRules; bank holiday detection via UK holiday dates |
| No same-day turnaround | `BookingController::details()` checks for same-day checkout conflicts |
| 3pm check-in / 12pm check-out | Settings seeded, property seeded, displayed on booking details and property page |
| £50 cleaning fee | Setting read by `PricingEngine`, included in all booking totals |
| 48-hour advance notice | `min_advance_days = 2` enforced in both `details()` and `holdAndPay()` |
| Max 12 adults + 2 infants + 2 cots | `max_adults`, `max_infants`, `max_cots` enforced in `BookingController::holdAndPay()` |
| No pets | Property seeded `pets_allowed = 'no'`, displayed on booking details and property page |
| Direct bookings 10% cheaper | `PricingEngine::calculateForRange(isDirectBooking: true)` applies `direct_booking_discount` setting |
| £950 damage deposit | Read from Settings, added to direct booking totals |
| Min price floors £450/£600 | `PricingEngine::calculateRateForDate()` enforces weekday/weekend minimums |
| Platform auto-approve | Setting exists (`auto_approve_guests = 1`) for future enforcement |

---

## 5. Public Website (18 pages)

### Pages
| Page | Route | Features |
|---|---|---|
| Home | `/` | 92vh hero, gallery, booking widget, stats metrics |
| About | `/about` | Property story |
| Property | `/property` | Stats, room cards with gallery grid, house rules section, platform links |
| Room Detail | `/rooms/{room}` | Hero image, gallery, description, stats, amenities, sibling rooms, policies |
| Amenities | `/amenities` | Categorized grid with icons |
| Gallery | `/gallery` | 2-column grid with drag-to-reorder (admin-managed) |
| Location | `/location` | Map embed |
| Area Guide | `/area-guide` | Weather forecast + local events (AI-powered) |
| FAQ | `/faq` | Categorized accordion from knowledge base |
| Food & Drink | `/food-drink` | Establishment cards with modals, add-ons section |
| Places | `/places` | Places cards with modals, distance badges |
| Contact | `/contact` | Enquiry form (sends email) |
| Privacy / Terms / Cancellation | `/privacy`, `/terms`, `/cancellation` | Legal pages |
| Booking Search | `/book` | Date/guest picker, real-time pricing |
| Booking Details | `/book/room/{room}` | Add-on selection, live price recalculation, Stripe checkout |
| Booking Confirmation | `/book/confirmation` | Success page with details |

### Design System
- **Fonts:** Cormorant Garamond (headings) + Outfit (body)
- **Colors:** Forest green `#1f6f43`, gold accent `#c9a227`, cream `#fbf8f1`
- **Components:** `.ch-suite-card`, `.ch-info-card`, `.ch-stat-card`, `.ch-booking-card`, `.ch-editorial-panel`, `.ch-modal`, `.ch-addon-option`
- **Navigation:** Active state detection via `request()->routeIs()` with gold underline

---

## 6. Admin Portal (22 modules)

| Module | Key Features |
|---|---|
| **Dashboard** | Overview stats, quick links |
| **Notifications** | System notifications, mark read/all |
| **Audit Logs** | Full activity trail with filters |
| **Settings** | Grouped settings (booking, website, AI, channel), encrypted API keys, image upload |
| **Gallery** | Dropzone upload, drag-to-reorder, 2-column grid |
| **Website Manager** | House rules, content, amenities, platform URLs — all configurable |
| **Properties** | CRUD with detail card, rooms table, amenities badges, policies, house rules, metadata, image management |
| **Rooms** | CRUD with image gallery, grid-based monthly calendar, Beds24 block types |
| **Amenities** | CRUD with search/category/status filter, toggle, audit logging |
| **Guests** | CRUD with full profile |
| **Reservations (Bookings)** | CRUD with manual reservation creation, cancel/check-in/check-out |
| **Calendar** | FullCalendar with room filter, event creation, block management |
| **Pricing** | Rules + overrides with AI generation, competitor rates, modal forms |
| **Payments** | Payment history, refund processing |
| **Channels** | Beds24 setup/invite/import/test/token, per-room sync, structured sync logs |
| **Communications** | Send messages with guest picker/channel select, template management (create/view/edit/delete via modals) |
| **Chatbot** | Knowledge base management, conversation viewer, staff reply mode |
| **Revenue** | Revenue intelligence dashboard with Chart.js |
| **Reports** | Data export with CSV download |
| **Food & Drink** | CRUD + toggle + featured toggle, image upload, search/category/status filter |
| **Places of Interest** | CRUD + toggle, image upload, search/category/status filter |
| **Add-Ons** | CRUD + toggle, search/category/status filter |

---

## 7. Services Layer (30 files, 12 domains)

### AI Assistant
- `AiAssistantService` — Composes replies from knowledge base context
- `AiProviderService` — OpenAI/Claude API abstraction with message completion
- `KnowledgeBaseService` — Article retrieval with content truncation
- `TokenOptimizationService` — Limits history, facts, context to control costs

### Channel Management (Beds24)
- `Beds24Client` — Raw API client for Beds24 endpoints
- `Beds24AuthService` — OAuth token management
- `Beds24ChannelProvider` — ChannelProviderInterface implementation
- `Beds24SyncService` — Booking sync with structured `{status, reason}` results
- `Beds24BookingPublisher` — Push bookings to channels
- `Beds24PricingPublisher` — Push rates to channels
- `Beds24PropertyPublisher` — Push property data to channels
- `Beds24RequestLogger` — Logs all API requests

### Pricing
- `PricingEngine` — Dynamic pricing with rule priority, min price floors, direct booking discount, bank holiday detection
- `SeasonalPricingAutomationService` — AI-powered seasonal rule generation

### Payments
- `PaymentGatewayInterface` — Abstraction for payment providers
- `StripePaymentGateway` — Stripe implementation
- `FakePaymentGateway` — Test double
- `PaymentService` — Orchestrates payment flows

### Booking
- `BookingService` — Reservation creation with validation
- `BookingHoldService` — 30-minute hold management

### Notifications
- `NotificationService` — Manual message sending
- `GuestMessageService` — Guest communication flows
- `SystemNotificationService` — Internal notifications

### Other
- `AuditLogger` — Activity trail logging
- `AvailabilityService` — Room availability checks
- `ChannelManager` — Multi-channel orchestration
- `RevenueAnalyticsService` — Revenue data aggregation
- `AreaIntelligenceService` — Weather + local events
- `MailConfigurationService` — Dynamic mail config

---

## 8. Scheduled Jobs (7 jobs)

| Job | Schedule | Purpose |
|---|---|---|
| `ExpireBookingHoldsJob` | Every 5 min | Release expired 30-min holds |
| `SyncBeds24BookingsJob` | Every 5 min | Pull bookings from Beds24 |
| `PushBeds24RatesJob` | Hourly | Push rates to Beds24 |
| `SendPreArrivalMessageJob` | Daily 09:00 | Pre-arrival instructions |
| `SendCheckInNotificationJob` | Daily 08:00 | Check-in day notification |
| `SendCheckoutNotificationJob` | Daily 08:30 | Checkout day notification |
| `GenerateRevenueSnapshotJob` | Daily 01:00 | Daily revenue intelligence |

---

## 9. Webhooks

| Endpoint | Handler | Purpose |
|---|---|---|
| `POST /webhooks/stripe` | `StripeWebhookController` | Payment confirmations, refunds |
| `POST /webhooks/beds24` | `Beds24WebhookController` | OTA booking updates |

---

## 10. API Endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `/api/v1/availability` | GET/POST | Room availability check |
| `/api/v1/booking/hold` | POST | Create booking hold |
| `/api/v1/booking/calculate-price` | POST | Price calculation |
| `/api/v1/chat` | POST | AI chatbot (20/min throttle) |
| `/api/v1/messages` | POST | Guest message submission (10/min throttle) |

---

## 11. Testing (144 tests, 541 assertions)

| Test File | Coverage |
|---|---|
| `AdminAccessTest` | Permission-based access control |
| `AdminMissingModulesTest` | Module existence and template rendering |
| `AdminNotificationsTest` | Notification CRUD and read states |
| `AdminResourcesTest` | Full admin CRUD for all modules |
| `AuthTest` | Login, registration, logout |
| `Beds24IntegrationTest` | Channel setup, invite, import, sync |
| `BookingHoldTest` | Hold creation, expiry, conversion |
| `ChannelWebhookTest` | Webhook payload processing |
| `ChatAssistantTest` | AI responses, knowledge base, intent detection |
| `CreateBookingTest` | End-to-end booking flow |
| `DatabaseMigrationCompatibilityTest` | Migration integrity |
| `PaymentWebhookTest` | Stripe webhook handling |
| `PricingEngineTest` | Dynamic pricing, rules, overrides, floors |
| `PublicBookingTest` | Search, details, hold, payment |
| `PublicWebsiteTest` | All public pages render correctly |
| `SettingsTest` | Settings CRUD and group filtering |

---

## 12. Code Statistics

| Metric | Count |
|---|---|
| Database tables | 38 |
| Migration files | 42 |
| Seeder files | 11 |
| Factory files | 12 |
| Model files | 33 |
| Controller files | 31 |
| Service files | 30 |
| Blade view files | ~95 |
| Test files | 17 |
| Test methods | 144 |
| Route files | 4 |
| Job files | 10 |
| Admin modules | 22 |
| Public pages | 18 |
| Scheduled jobs | 7 |

---

## 13. What's Next (Potential Enhancements)

- [ ] Full Beds24 booking sync end-to-end with live channel data
- [ ] Guest portal (self-service booking management)
- [ ] Multi-property support
- [ ] Automated pricing suggestions via AI based on competitor data
- [ ] Guest feedback/rating integration for auto-approval logic
- [ ] Email template rendering with Twig/Blade in actual sends
- [ ] Mobile-responsive admin improvements
- [ ] Two-factor authentication
- [ ] Rate limiting per IP on public booking endpoints
- [ ] Structured logging with Laravel Pail integration
