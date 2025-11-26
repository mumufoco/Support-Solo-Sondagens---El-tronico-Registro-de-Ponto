<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /*
     * --------------------------------------------------------------------
     * Session Service - REMOVED
     * --------------------------------------------------------------------
     *
     * The custom session() override has been removed because it causes
     * circular dependency issues during early boot:
     *
     * - session() calls logger()
     * - logger() calls config() helper
     * - config() helper doesn't exist until boot is complete
     * - Result: Fatal error before app can start
     *
     * Session configuration is now handled through:
     * - app/Config/Session.php (sets SafeFileHandler as default driver)
     * - CodeIgniter's default session service (uses driver from config)
     *
     * SafeFileHandler is automatically used because it's set as the
     * default driver in Session.php line 29.
     */
}
