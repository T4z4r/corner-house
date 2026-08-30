<?php

use App\Http\Controllers\Admin\AmenityController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\ChatbotController;
use App\Http\Controllers\Admin\CommunicationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Website\BookingController;
use App\Http\Controllers\Website\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebsiteController::class, 'home'])->name('home');
Route::get('/about', [WebsiteController::class, 'about'])->name('about');
Route::get('/property', [WebsiteController::class, 'property'])->name('property');
Route::get('/amenities', [WebsiteController::class, 'amenities'])->name('amenities');
Route::get('/gallery', [WebsiteController::class, 'gallery'])->name('gallery');
Route::get('/location', [WebsiteController::class, 'location'])->name('location');
Route::get('/faq', [WebsiteController::class, 'faq'])->name('faq');
Route::get('/contact', [WebsiteController::class, 'contact'])->name('contact');
Route::post('/contact', [WebsiteController::class, 'submitContact'])->name('contact.submit');
Route::get('/privacy', [WebsiteController::class, 'privacy'])->name('privacy');
Route::get('/terms', [WebsiteController::class, 'terms'])->name('terms');
Route::get('/cancellation-policy', [WebsiteController::class, 'cancellation'])->name('cancellation');

Route::get('/book', [BookingController::class, 'search'])->name('booking.search');
Route::get('/book/room/{room}', [BookingController::class, 'details'])->name('booking.details');
Route::post('/book/pay', [BookingController::class, 'holdAndPay'])->name('booking.pay');
Route::get('/book/confirmation', [BookingController::class, 'confirmation'])->name('booking.confirmation');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('can:audit_logs.view')->group(function (): void {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
    });

    Route::middleware('can:settings.view')->group(function (): void {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::get('/settings/mail', [SettingsController::class, 'mail'])->name('settings.mail');
        Route::get('/settings/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
        Route::get('/settings/website', [SettingsController::class, 'website'])->name('settings.website');
        Route::post('/settings/upload-image', [SettingsController::class, 'uploadImage'])->name('settings.upload-image')->middleware('can:settings.update');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');

        Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
        Route::post('/gallery/upload', [GalleryController::class, 'upload'])->name('gallery.upload');
        Route::put('/gallery/{image}', [GalleryController::class, 'update'])->name('gallery.update');
        Route::delete('/gallery/{image}', [GalleryController::class, 'destroy'])->name('gallery.destroy');
        Route::put('/gallery/reorder', [GalleryController::class, 'reorder'])->name('gallery.reorder');
        Route::post('/gallery/delete-uploaded', [GalleryController::class, 'destroyUploaded'])->name('gallery.delete-uploaded');
    });

    Route::middleware('can:users.view')->group(function (): void {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create')->middleware('can:users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('can:users.create');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('can:users.update');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('can:users.delete');
    });

    Route::middleware('can:properties.view')->group(function (): void {
        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/create', [PropertyController::class, 'create'])->name('properties.create')->middleware('can:properties.create');
        Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store')->middleware('can:properties.create');
        Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
        Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit')->middleware('can:properties.update');
        Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update')->middleware('can:properties.update');
        Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy')->middleware('can:properties.delete');
        Route::post('/properties/upload-image', [PropertyController::class, 'uploadImage'])->name('properties.upload-image')->middleware('can:properties.update');
        Route::delete('/properties/image/{image}', [PropertyController::class, 'destroyImage'])->name('properties.image.destroy')->middleware('can:properties.update');
    });

    Route::middleware('can:amenities.view')->group(function (): void {
        Route::get('/amenities', [AmenityController::class, 'index'])->name('amenities.index');
        Route::get('/amenities/create', [AmenityController::class, 'create'])->name('amenities.create')->middleware('can:amenities.create');
        Route::post('/amenities', [AmenityController::class, 'store'])->name('amenities.store')->middleware('can:amenities.create');
        Route::get('/amenities/{amenity}/edit', [AmenityController::class, 'edit'])->name('amenities.edit')->middleware('can:amenities.update');
        Route::put('/amenities/{amenity}', [AmenityController::class, 'update'])->name('amenities.update')->middleware('can:amenities.update');
        Route::post('/amenities/{amenity}/toggle', [AmenityController::class, 'toggle'])->name('amenities.toggle')->middleware('can:amenities.update');
        Route::delete('/amenities/{amenity}', [AmenityController::class, 'destroy'])->name('amenities.destroy')->middleware('can:amenities.delete');
    });

    Route::middleware('can:rooms.view')->group(function (): void {
        Route::get('/rooms', [RoomController::class, 'manage'])->name('rooms.manage');
        Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
    });

    Route::middleware('can:rooms.update')->group(function (): void {
        Route::get('/rooms/{room}/edit', [RoomController::class, 'edit'])->name('rooms.edit');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy')->middleware('can:rooms.delete');
        Route::delete('/rooms/image/{image}', [RoomController::class, 'destroyImage'])->name('rooms.image.destroy');
        Route::post('/rooms/upload-image', [RoomController::class, 'uploadImage'])->name('rooms.upload-image');
        Route::post('/rooms/delete-uploaded-image', [RoomController::class, 'destroyUploadedImage'])->name('rooms.delete-uploaded-image');
    });

    Route::prefix('properties')->group(function (): void {
        Route::get('/{property}/rooms', [RoomController::class, 'index'])->name('rooms.index')->middleware('can:rooms.view');
        Route::get('/{property}/rooms/create', [RoomController::class, 'create'])->name('rooms.create')->middleware('can:rooms.create');
        Route::post('/{property}/rooms', [RoomController::class, 'store'])->name('rooms.store')->middleware('can:rooms.create');
    });

    Route::middleware('can:guests.view')->group(function (): void {
        Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
        Route::get('/guests/create', [GuestController::class, 'create'])->name('guests.create')->middleware('can:guests.create');
        Route::post('/guests', [GuestController::class, 'store'])->name('guests.store')->middleware('can:guests.create');
        Route::get('/guests/{guest}', [GuestController::class, 'show'])->name('guests.show');
        Route::get('/guests/{guest}/edit', [GuestController::class, 'edit'])->name('guests.edit')->middleware('can:guests.update');
        Route::put('/guests/{guest}', [GuestController::class, 'update'])->name('guests.update')->middleware('can:guests.update');
        Route::delete('/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy')->middleware('can:guests.delete');
    });

    Route::middleware('can:reservations.view')->group(function (): void {
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create')->middleware('can:reservations.create');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store')->middleware('can:reservations.create');
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel')->middleware('can:reservations.cancel');
        Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])->name('reservations.check-in')->middleware('can:reservations.update');
        Route::post('/reservations/{reservation}/check-out', [ReservationController::class, 'checkOut'])->name('reservations.check-out')->middleware('can:reservations.update');
    });

    Route::middleware('can:calendar.view')->group(function (): void {
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
        Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::post('/calendar/blocks', [CalendarController::class, 'storeBlock'])->name('calendar.blocks.store')->middleware('can:calendar.manage');
    });

    Route::middleware('can:pricing.view')->group(function (): void {
        Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
        Route::post('/pricing/rules', [PricingController::class, 'storeRule'])->name('pricing.rules.store')->middleware('can:pricing.create');
        Route::put('/pricing/rules/{rule}', [PricingController::class, 'updateRule'])->name('pricing.rules.update')->middleware('can:pricing.update');
        Route::delete('/pricing/rules/{rule}', [PricingController::class, 'destroyRule'])->name('pricing.rules.destroy')->middleware('can:pricing.delete');
        Route::post('/pricing/overrides', [PricingController::class, 'storeOverride'])->name('pricing.overrides.store')->middleware('can:pricing.create');
        Route::delete('/pricing/overrides/{override}', [PricingController::class, 'destroyOverride'])->name('pricing.overrides.destroy')->middleware('can:pricing.delete');
    });

    Route::middleware('can:payments.view')->group(function (): void {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund')->middleware('can:payments.refund');
    });

    Route::middleware('can:channels.view')->group(function (): void {
        Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
        Route::get('/channels/integrations', [ChannelController::class, 'integrations'])->name('channels.integrations');
        Route::get('/channels/setup', [ChannelController::class, 'setupPage'])->name('channels.setup.page');
        Route::get('/channels/airbnb', [ChannelController::class, 'airbnb'])->name('channels.airbnb');
        Route::get('/channels/booking', [ChannelController::class, 'booking'])->name('channels.booking');
        Route::get('/channels/vrbo', [ChannelController::class, 'vrbo'])->name('channels.vrbo');
        Route::post('/channels/prices/import', [ChannelController::class, 'importPrices'])->name('channels.prices.import')->middleware('can:channels.configure');
        Route::post('/channels/properties/{property}/publish', [ChannelController::class, 'publishProperty'])->name('channels.properties.publish')->middleware('can:channels.configure');
        Route::post('/channels/bookings/{reservation}/publish', [ChannelController::class, 'publishBooking'])->name('channels.bookings.publish')->middleware('can:channels.configure');
        Route::post('/channels/bookings/{reservation}/guests', [ChannelController::class, 'publishGuests'])->name('channels.bookings.guests.publish')->middleware('can:channels.configure');
        Route::post('/channels/pricing/rules/{rule}/publish', [ChannelController::class, 'publishPricingRule'])->name('channels.pricing.rules.publish')->middleware('can:channels.configure');
        Route::post('/channels/pricing/overrides/{override}/publish', [ChannelController::class, 'publishPricingOverride'])->name('channels.pricing.overrides.publish')->middleware('can:channels.configure');
        Route::post('/channels/airbnb/actions', [ChannelController::class, 'airbnbAction'])->name('channels.airbnb.actions')->middleware('can:channels.configure');
        Route::post('/channels', [ChannelController::class, 'store'])->name('channels.store')->middleware('can:channels.configure');
        Route::get('/channels/{account}/edit', [ChannelController::class, 'edit'])->name('channels.edit')->middleware('can:channels.configure');
        Route::put('/channels/{account}', [ChannelController::class, 'update'])->name('channels.update')->middleware('can:channels.configure');
        Route::delete('/channels/{account}', [ChannelController::class, 'destroy'])->name('channels.destroy')->middleware('can:channels.configure');
        Route::post('/channels/credentials', [ChannelController::class, 'setupCredentials'])->name('channels.credentials.setup')->middleware('can:channels.configure');
        Route::post('/channels/mappings', [ChannelController::class, 'storeMapping'])->name('channels.mappings.store')->middleware('can:channels.configure');
        Route::post('/channels/import', [ChannelController::class, 'import'])->name('channels.import')->middleware('can:channels.configure');
        Route::post('/channels/properties/sync', [ChannelController::class, 'syncProperties'])->name('channels.properties.sync')->middleware('can:channels.configure');
        Route::post('/channels/rooms/sync', [ChannelController::class, 'syncRooms'])->name('channels.rooms.sync')->middleware('can:channels.configure');
        Route::post('/channels/{account}/setup', [ChannelController::class, 'setup'])->name('channels.setup')->middleware('can:channels.configure');
        Route::post('/channels/{account}/details', [ChannelController::class, 'details'])->name('channels.details')->middleware('can:channels.configure');
        Route::post('/channels/{account}/test', [ChannelController::class, 'test'])->name('channels.test')->middleware('can:channels.configure');
        Route::post('/channels/sync', [ChannelController::class, 'sync'])->name('channels.sync')->middleware('can:channels.sync');
    });

    Route::middleware('can:communications.view')->group(function (): void {
        Route::get('/communications', [CommunicationController::class, 'index'])->name('communications.index');
        Route::post('/communications/templates', [CommunicationController::class, 'storeTemplate'])->name('communications.templates.store')->middleware('can:communications.manage_templates');
        Route::post('/communications/send', [CommunicationController::class, 'send'])->name('communications.send')->middleware('can:communications.send');
    });

    Route::middleware('can:chatbot.view')->group(function (): void {
        Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
        Route::get('/chatbot/conversations/{conversation}', [ChatbotController::class, 'showConversation'])->name('chatbot.conversations.show');
        Route::post('/chatbot/articles', [ChatbotController::class, 'storeArticle'])->name('chatbot.articles.store')->middleware('can:chatbot.manage');
        Route::put('/chatbot/articles/{article}', [ChatbotController::class, 'updateArticle'])->name('chatbot.articles.update')->middleware('can:chatbot.manage');
        Route::post('/chatbot/messages/{message}/flag', [ChatbotController::class, 'flagMessage'])->name('chatbot.messages.flag')->middleware('can:chatbot.manage');
        Route::post('/chatbot/conversations/{conversation}/reply', [ChatbotController::class, 'reply'])->name('chatbot.conversations.reply')->middleware('can:chatbot.manage');
    });

    Route::middleware('can:reports.view')->group(function (): void {
        Route::get('/revenue', [RevenueController::class, 'index'])->name('revenue.index');
        Route::get('/revenue/chart', [RevenueController::class, 'chart'])->name('revenue.chart');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export')->middleware('can:reports.export');
    });
});
