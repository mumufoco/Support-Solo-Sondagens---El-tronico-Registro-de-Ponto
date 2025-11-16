<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;

/**
 * Setup how the exception handler works.
 */
class Exceptions extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * HIDE OR SHOW SENSITIVE DATA IN ERRORS?
     * --------------------------------------------------------------------------
     * Any data that you would want to hide from the debug trace.
     * In order to specify 2 levels, use "/" to separate.
     * ex. ['server', 'setup/password', 'secret_token']
     *
     * @var list<string>
     */
    public array $sensitiveDataInTrace = [];

    /**
     * --------------------------------------------------------------------------
     * LOG ERRORS?
     * --------------------------------------------------------------------------
     * If true, then errors will be logged to the ErrorLog.
     *
     * @var bool
     */
    public $log = true;

    /**
     * --------------------------------------------------------------------------
     * DO NOT LOG STATUS CODES
     * --------------------------------------------------------------------------
     * Any status codes here will NOT be logged if logging is turned on.
     * By default, only 404 (Page Not Found) errors are ignored.
     *
     * @var list<int>
     */
    public $ignoredCodes = [404];

    /**
     * --------------------------------------------------------------------------
     * Error Views Path
     * --------------------------------------------------------------------------
     * This is the path to the directory that contains the 'cli' and 'html'
     * directories that hold the views used to generate error output.
     *
     * @var string
     */
    public $errorViewPath = APPPATH . 'Views/errors';

    /**
     * --------------------------------------------------------------------------
     * SPECIFY HANDLER
     * --------------------------------------------------------------------------
     * You can specify a custom exception handler. It must be an instance of
     * ExceptionHandlerInterface.
     *
     * @var class-string<ExceptionHandlerInterface>
     */
    public $handler = ExceptionHandler::class;
}
