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
    /**
     * The Session class - Override to FORCE SafeFileHandler
     *
     * This override is necessary because the default service ignores
     * the driver setting in Session.php and uses FileHandler instead.
     *
     * We FORCE SafeFileHandler to avoid ini_set() calls that fail.
     */
    public static function session(?\Config\Session $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('session', $config);
        }

        // Get config
        if ($config === null) {
            $config = new \Config\Session();
        }

        // FORCE SafeFileHandler - no logger, no other dependencies
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $driver = new \App\Session\Handlers\SafeFileHandler($config, $ipAddress);

        // Create SafeSession (NOT regular Session) to avoid ini_set()
        $session = new \App\Libraries\SafeSession($driver, $config);
        $session->start();

        return $session;
    }
}
