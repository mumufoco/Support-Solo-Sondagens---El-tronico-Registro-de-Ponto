<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default route
$routes->get('/', 'Home::index');

/*
 * --------------------------------------------------------------------
 * Authentication Routes
 * --------------------------------------------------------------------
 */
$routes->group('auth', static function ($routes) {
    $routes->get('login', 'Auth\LoginController::index', ['as' => 'login']);
    $routes->post('login', 'Auth\LoginController::authenticate');
    $routes->get('register', 'Auth\RegisterController::index');
    $routes->post('register', 'Auth\RegisterController::store');
    $routes->get('logout', 'Auth\LogoutController::logout', ['as' => 'logout']);
});

/*
 * --------------------------------------------------------------------
 * Dashboard Routes (Require Authentication)
 * --------------------------------------------------------------------
 */
$routes->group('dashboard', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Dashboard\DashboardController::index');
    $routes->get('admin', 'Dashboard\AdminDashboardController::index', ['filter' => 'admin']);
    $routes->get('manager', 'Dashboard\ManagerDashboardController::index', ['filter' => 'manager']);
    $routes->get('employee', 'Dashboard\EmployeeDashboardController::index');
});

/*
 * --------------------------------------------------------------------
 * Time Punch Routes
 * --------------------------------------------------------------------
 */
$routes->group('timesheet', ['filter' => 'auth'], static function ($routes) {
    // Punch routes
    $routes->get('punch', 'Timesheet\TimePunchController::index');
    $routes->post('punch', 'Timesheet\TimePunchController::punch');
    $routes->post('punch/code', 'Timesheet\TimePunchController::punchByCode');
    $routes->post('punch/qr', 'Timesheet\TimePunchController::punchByQR');
    $routes->post('punch/face', 'Timesheet\TimePunchController::punchByFace');

    // Timesheet history
    $routes->get('history', 'Timesheet\TimesheetController::index');
    $routes->get('history/(:num)', 'Timesheet\TimesheetController::show/$1');
    $routes->get('balance', 'Timesheet\TimesheetController::balance');

    // Receipt
    $routes->get('receipt/(:num)', 'Timesheet\TimePunchController::receipt/$1');
});

/*
 * --------------------------------------------------------------------
 * Justification Routes
 * --------------------------------------------------------------------
 */
$routes->group('justifications', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Timesheet\JustificationController::index');
    $routes->get('create', 'Timesheet\JustificationController::create');
    $routes->post('store', 'Timesheet\JustificationController::store');
    $routes->get('(:num)', 'Timesheet\JustificationController::show/$1');
    $routes->post('(:num)/approve', 'Timesheet\JustificationController::approve/$1', ['filter' => 'manager']);
    $routes->post('(:num)/reject', 'Timesheet\JustificationController::reject/$1', ['filter' => 'manager']);
});

/*
 * --------------------------------------------------------------------
 * Employee Routes (Manager and Admin only)
 * --------------------------------------------------------------------
 */
$routes->group('employees', ['filter' => 'auth|manager'], static function ($routes) {
    $routes->get('/', 'Employee\EmployeeController::index');
    $routes->get('create', 'Employee\EmployeeController::create');
    $routes->post('store', 'Employee\EmployeeController::store');
    $routes->get('(:num)', 'Employee\EmployeeController::show/$1');
    $routes->get('(:num)/edit', 'Employee\EmployeeController::edit/$1');
    $routes->post('(:num)/update', 'Employee\EmployeeController::update/$1');
    $routes->delete('(:num)', 'Employee\EmployeeController::delete/$1');

    // QR Code
    $routes->get('(:num)/qrcode', 'Employee\EmployeeController::qrcode/$1');
});

/*
 * --------------------------------------------------------------------
 * Biometric Routes
 * --------------------------------------------------------------------
 */
$routes->group('biometric', ['filter' => 'auth|manager'], static function ($routes) {
    // Face recognition
    $routes->get('face/enroll/(:num)', 'Biometric\FaceRecognitionController::enroll/$1');
    $routes->post('face/enroll', 'Biometric\FaceRecognitionController::store');
    $routes->post('face/test/(:num)', 'Biometric\FaceRecognitionController::test/$1');
    $routes->delete('face/(:num)', 'Biometric\FaceRecognitionController::delete/$1');

    // Fingerprint (optional)
    $routes->get('fingerprint/enroll/(:num)', 'Biometric\FingerprintController::enroll/$1');
    $routes->post('fingerprint/enroll', 'Biometric\FingerprintController::store');
    $routes->delete('fingerprint/(:num)', 'Biometric\FingerprintController::delete/$1');
});

/*
 * --------------------------------------------------------------------
 * Geofence Routes (Admin only)
 * --------------------------------------------------------------------
 */
$routes->group('geofence', ['filter' => 'auth|admin'], static function ($routes) {
    $routes->get('/', 'Geolocation\GeofenceController::index');
    $routes->get('map', 'Geolocation\GeofenceController::map');
    $routes->post('store', 'Geolocation\GeofenceController::store');
    $routes->put('(:num)', 'Geolocation\GeofenceController::update/$1');
    $routes->delete('(:num)', 'Geolocation\GeofenceController::delete/$1');
});

/*
 * --------------------------------------------------------------------
 * Report Routes
 * --------------------------------------------------------------------
 */
$routes->group('reports', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Report\ReportController::index');
    $routes->post('generate', 'Report\ReportController::generate');
    $routes->get('download/(:any)', 'Report\ReportController::download/$1');
});

/*
 * --------------------------------------------------------------------
 * Chat Routes (WebSocket Real-time Chat)
 * --------------------------------------------------------------------
 */
$routes->group('chat', ['filter' => 'auth'], static function ($routes) {
    // Main chat interface
    $routes->get('/', 'ChatController::index');

    // Room operations
    $routes->get('room/(:num)', 'ChatController::room/$1');
    $routes->get('room/(:num)/settings', 'ChatController::roomSettings/$1');

    // Create new chats
    $routes->get('new/(:num)', 'ChatController::newChat/$1');
    $routes->get('group/create', 'ChatController::createGroup');
    $routes->post('group/store', 'ChatController::storeGroup');

    // Member management
    $routes->post('room/(:num)/add-member', 'ChatController::addMember/$1');
    $routes->post('room/(:num)/remove-member', 'ChatController::removeMember/$1');

    // Search
    $routes->get('room/(:num)/search', 'ChatController::search/$1');

    // File upload/download
    $routes->post('upload', 'ChatController::uploadFile');
    $routes->get('file/download', 'ChatController::downloadFile');
});

/*
 * --------------------------------------------------------------------
 * Warning Routes (Manager and Admin only)
 * --------------------------------------------------------------------
 */
$routes->group('warnings', ['filter' => 'auth|manager'], static function ($routes) {
    $routes->get('/', 'Warning\WarningController::index');
    $routes->get('create', 'Warning\WarningController::create');
    $routes->post('store', 'Warning\WarningController::store');
    $routes->get('(:num)', 'Warning\WarningController::show/$1');
    $routes->post('(:num)/sign', 'Warning\WarningController::sign/$1');
    $routes->post('(:num)/refuse', 'Warning\WarningController::refuseSignature/$1');
});

/*
 * --------------------------------------------------------------------
 * LGPD Routes
 * --------------------------------------------------------------------
 */
$routes->group('lgpd', ['filter' => 'auth'], static function ($routes) {
    $routes->get('consents', 'Setting\SettingController::consents');
    $routes->post('consent/grant', 'Setting\SettingController::grantConsent');
    $routes->post('consent/revoke', 'Setting\SettingController::revokeConsent');
    $routes->get('export', 'Setting\SettingController::exportData');
});

/*
 * --------------------------------------------------------------------
 * Settings Routes (Admin only)
 * --------------------------------------------------------------------
 */
$routes->group('settings', ['filter' => 'auth|admin'], static function ($routes) {
    $routes->get('/', 'Setting\SettingController::index');
    $routes->post('update', 'Setting\SettingController::update');

    // Audit logs
    $routes->get('audit', 'Setting\SettingController::audit');
});

/*
 * --------------------------------------------------------------------
 * API Routes
 * --------------------------------------------------------------------
 */
$routes->group('api', ['filter' => 'cors'], static function ($routes) {
    // Validation
    $routes->post('validate-code', 'Api\ApiController::validateCode');

    // Health check
    $routes->get('health', 'Api\ApiController::health');

    // DeepFace proxy (internal use)
    $routes->post('deepface/enroll', 'Api\ApiController::deepfaceEnroll');
    $routes->post('deepface/recognize', 'Api\ApiController::deepfaceRecognize');

    /*
     * Chat API Routes (RESTful)
     */
    $routes->group('chat', ['filter' => 'api-auth'], static function ($routes) {
        // Rooms
        $routes->get('rooms', 'API\ChatAPIController::getRooms');
        $routes->post('rooms/private', 'API\ChatAPIController::createPrivateRoom');
        $routes->post('rooms/group', 'API\ChatAPIController::createGroupRoom');

        // Room Messages
        $routes->get('rooms/(:num)/messages', 'API\ChatAPIController::getMessages/$1');
        $routes->post('rooms/(:num)/messages', 'API\ChatAPIController::sendMessage/$1');
        $routes->post('rooms/(:num)/read', 'API\ChatAPIController::markAsRead/$1');
        $routes->get('rooms/(:num)/search', 'API\ChatAPIController::searchMessages/$1');

        // Room Members
        $routes->get('rooms/(:num)/members', 'API\ChatAPIController::getMembers/$1');
        $routes->post('rooms/(:num)/members', 'API\ChatAPIController::addMember/$1');
        $routes->delete('rooms/(:num)/members/(:num)', 'API\ChatAPIController::removeMember/$1/$2');

        // Messages
        $routes->put('messages/(:num)', 'API\ChatAPIController::editMessage/$1');
        $routes->delete('messages/(:num)', 'API\ChatAPIController::deleteMessage/$1');
        $routes->post('messages/(:num)/reactions', 'API\ChatAPIController::addReaction/$1');

        // Online Users
        $routes->get('online', 'API\ChatAPIController::getOnlineUsers');
    });
});
