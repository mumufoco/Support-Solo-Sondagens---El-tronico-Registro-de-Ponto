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
     * The Session class.
     *
     * Overrides the default Session service to use SafeSession
     * which avoids ini_set() calls that may fail in shared hosting.
     *
     * @param \Config\Session|null $config
     * @param bool                 $getShared
     *
     * @return \App\Libraries\SafeSession
     */
    public static function session(?\Config\Session $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('session', $config);
        }

        $config ??= new \Config\Session();

        $logger = static::logger();

        // ALWAYS use SafeFileHandler to avoid ini_set() issues
        // Force SafeFileHandler regardless of configuration
        $driver = new \App\Session\Handlers\SafeFileHandler($config, static::request()->getIPAddress());
        $driver->setLogger($logger);

        return new \App\Libraries\SafeSession($driver, $config);
    }
}
