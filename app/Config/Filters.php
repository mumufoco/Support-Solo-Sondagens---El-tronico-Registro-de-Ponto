<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, string>
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,

        // Custom filters
        'auth'             => \App\Filters\AuthFilter::class,
        'api-auth'         => \App\Filters\AuthFilter::class, // API authentication (same as auth, returns JSON)
        'admin'            => \App\Filters\AdminFilter::class,
        'manager'          => \App\Filters\ManagerFilter::class,
        'cors'             => \App\Filters\CorsFilter::class,
        'ratelimit'        => \App\Filters\RateLimitFilter::class,
        'securityheaders'  => \App\Filters\SecurityHeadersFilter::class,
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, list<string>>
     */
    public array $globals = [
        'before' => [
            // Security filters
            'invalidchars',
            'secureheaders',

            // CORS for API routes
            'cors' => ['except' => [
                '/',
                'auth/*',
                'dashboard/*',
                'employees/*',
                'timesheet/index',
            ]],

            // SECURITY FIX: Rate limiting for auth routes including password reset
            // Prevents brute force attacks on authentication and password recovery
            'ratelimit:auth' => ['filter' => [
                'auth/login',
                'auth/register',
                'auth/forgot-password',
                'auth/reset-password',
            ]],

            // Rate limiting for punch routes
            'ratelimit:punch' => ['filter' => [
                'timesheet/punch*',
                'api/punch*',
            ]],

            // Rate limiting for API routes
            'ratelimit:api' => ['filter' => [
                'api/*',
            ]],
        ],
        'after' => [
            'toolbar',
            'cors',
            'securityheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        // SECURITY FIX: CSRF Protection for state-changing operations
        // Prevents Cross-Site Request Forgery attacks on POST endpoints
        'csrf' => [
            'before' => [
                'chat/group/store',
                'chat/room/*/add-member',
                'chat/room/*/remove-member',
                'chat/upload',
                'chat/push/subscribe',
                'chat/push/unsubscribe',
                'chat/push/test',
                'employees/store',
                'employees/update/*',
                'employees/delete/*',
                'employees/activate/*',
                'employees/deactivate/*',
                'timesheet/punch',
                'justifications/store',
                'justifications/approve/*',
                'justifications/reject/*',
                'biometric/upload',
                'biometric/delete/*',
                'settings/update',
                'warnings/acknowledge/*',
            ],
        ],

        // Authentication required
        'auth' => [
            'before' => [
                'dashboard',
                'dashboard/*',
                'employees/profile',
                'employees/update-profile',
                'employees/export-data/*',
                'biometric',
                'biometric/*',
                'timesheet/my-punches',
                'timesheet/generate-qrcode',
                'justifications',
                'justifications/*',
                'reports',
                'reports/*',
                'chat',
                'chat/*',
                'warnings/acknowledge/*',
                'lgpd',
                'lgpd/*',
                'settings/profile',
                'settings/update-profile',
            ],
        ],

        // Manager or Admin required
        'manager' => [
            'before' => [
                'employees',
                'employees/index',
                'employees/show/*',
                'employees/create',
                'employees/store',
                'employees/edit/*',
                'employees/update/*',
                'employees/activate/*',
                'employees/deactivate/*',
                'dashboard/manager',
                'justifications/approve/*',
                'justifications/reject/*',
                'reports/department',
                'reports/employee/*',
                'biometric/manage',
                'biometric/manage/*',
            ],
        ],

        // Admin only
        'admin' => [
            'before' => [
                'employees/delete/*',
                'dashboard/admin',
                'settings',
                'settings/*',
                'admin',
                'admin/*',
                'geofences',
                'geofences/*',
                'audit-logs',
                'audit-logs/*',
            ],
        ],
    ];
}
