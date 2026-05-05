<?php
// app/Config/Routes.php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/',         'Auth::login');
$routes->get('/login',    'Auth::login');
$routes->post('/login',   'Auth::loginPost');
$routes->get('/register', 'Auth::register');
$routes->post('/register','Auth::registerPost');
$routes->get('/logout',   'Auth::logout');

// ── Admin ─────────────────────────────────────────────────────
$routes->group('admin', ['filter' => 'auth:admin'], function ($routes) {

    $routes->get('dashboard', 'Dashboard::admin');

    // Rooms
    $routes->get ('rooms',               'Rooms::index');
    $routes->post('rooms/data',          'Rooms::getData');
    $routes->post('rooms/store',         'Rooms::store');
    $routes->get ('rooms/edit/(:num)',   'Rooms::edit/$1');
    $routes->post('rooms/update/(:num)', 'Rooms::update/$1');
    $routes->post('rooms/delete/(:num)', 'Rooms::delete/$1');

    // Bookings
    $routes->get ('bookings',               'Bookings::adminIndex');
    $routes->post('bookings/data',          'Bookings::adminData');
    $routes->post('bookings/status/(:num)', 'Bookings::updateStatus/$1');

    // Payments
    $routes->get ('payments',               'Payments::adminIndex');
    $routes->post('payments/data',          'Payments::adminData');
    $routes->post('payments/verify/(:num)', 'Payments::verify/$1');
    $routes->get ('payments/image/(:num)',  'Payments::viewImage/$1');
    
    // Installments
    $routes->get ('installments',               'Installments::adminIndex');
    $routes->post('installments/data',          'Installments::adminData');
    $routes->get ('installments/image/(:num)',  'Installments::viewImage/$1');
    $routes->post('installments/verify/(:num)', 'Installments::verify/$1');

    // Export / Import
    $routes->get ('export/bookings-excel',   'Export::bookingsExcel');
    $routes->get ('export/bookings-pdf',     'Export::bookingsReportPdf');
    $routes->get ('export/rooms-excel',      'Export::roomsExcel');
    $routes->post('export/import-rooms',     'Export::importRooms');
    $routes->get ('export/import-template',  'Export::importTemplate');

    // ── Invoice per booking (admin bisa download invoice siapapun) ──
    $routes->get('export/invoice/(:num)', 'Export::bookingPdf/$1');
});

// ── User ──────────────────────────────────────────────────────
$routes->group('user', ['filter' => 'auth:user'], function ($routes) {

    $routes->get('dashboard', 'Dashboard::user');

    // Rooms (read-only)
    $routes->get('rooms', 'Rooms::userIndex');

    // Bookings
    $routes->get ('bookings',              'Bookings::userIndex');
    $routes->post('bookings/data',         'Bookings::userData');
    $routes->post('bookings/store',        'Bookings::store');
    $routes->post('bookings/cancel/(:num)','Bookings::cancel/$1');

    // Payments
    $routes->get ('payments',              'Payments::userIndex');
    $routes->post('payments/data',         'Payments::userData');
    $routes->post('payments/upload/(:num)','Payments::upload/$1');

    // Installments
    $routes->get ('installments/(:num)',        'Installments::schedule/$1');
    $routes->post('installments/upload/(:num)', 'Installments::upload/$1');

    // Invoice (user hanya bisa download invoice miliknya sendiri)
    $routes->get('export/invoice/(:num)', 'Export::bookingPdf/$1');
});

// ── REST API ──────────────────────────────────────────────────
$routes->group('api', ['filter' => 'auth'], function ($routes) {
    $routes->get('rooms',    'api\RoomsApi::index');
    $routes->get('bookings', 'api\BookingsApi::index');
});