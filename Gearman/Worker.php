<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Gearman
 * @subpackage Gearman_Worker
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * Gearman Worker
 * Implementation for handling Gearman Work
 *
 * @package    Gearman
 * @subpackage Gearman_Worker
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Gearman_Worker
{
    /**
     * Register Function
     * @var string
     */
    protected $_registerFunction;

    /**
     * Gearman Timeout
     * @var int
     */
    protected $_timeout = 60000;

    /**
     * Alloted Memory Limit in MB
     * @var int
     */
    protected $_memory = 1024;

    /**
     * Error Message
     * @var string
     */
    protected $_error = null;

    /**
     * Gearman Worker
     * @var GearmanWorker
     */
    protected $_worker;

    /**
     * Bootstrap
     * @var Zend_Application_Bootstrap_BootstrapAbstract
     */
    protected $_bootstrap;

    /**
     * Constructor
     * Checks for the required gearman extension,
     * fetches the bootstrap and loads in the gearman worker
     *
     * @param Zend_Application_Bootstrap_BootstrapAbstract $bootstrap
     * @return Gearman_Worker
     */
    public function __construct(Zend_Application_Bootstrap_BootstrapAbstract $bootstrap)
    {
        if (!extension_loaded('gearman')) {
            throw new RuntimeException('The PECL::gearman extension is required.');
        }
        $this->_bootstrap = $bootstrap;
        $this->_worker = $this->_bootstrap->bootstrap('gearmanworker')
                              ->getResource('gearmanworker');
        if (empty($this->_registerFunction)) {
            throw new InvalidArgumentException(get_class($this) . ' must implement a registerFunction');
        }
        // allow for a small memory gap:
        $memoryLimit = ($this->_memory + 128) * 1024 * 1024;
        ini_set('memory_limit', $memoryLimit);
        $this->_worker->addFunction($this->_registerFunction, array(&$this, 'work'));
        $this->_worker->setTimeout($this->_timeout);
        $this->init();

        $check = 10;
        $c = 0;
        while (@$this->_worker->work() || $this->_worker->returnCode() == GEARMAN_TIMEOUT) {
            $c++;

            if ($this->_worker->returnCode() == GEARMAN_TIMEOUT) {
                $this->timeout();
                continue;
            }

            if ($this->_worker->returnCode() != GEARMAN_SUCCESS) {
                $this->setError($this->_worker->returnCode() . ': ' . $this->_worker->getErrno() . ': ' . $this->_worker->error());
                break;
            }

            if (($c % $check === 0) && $this->isMemoryOverflow()) {
                break; // we've consumed our memory and the worker needs to be restarted
            }

        }

        $this->shutdown();
    }

    /**
     * Initialization
     *
     * @return void
     */
    protected function init()
    {

    }

    /**
     * Handle Timeout
     *
     * @return void
     */
    protected function timeout()
    {

    }

    /**
     * Handle Shutdown
     *
     * @return void
     */
    protected function shutdown()
    {

    }

    /**
     * Checks for a Memory Overflow from our Limit
     *
     * @return bool
     */
    protected function isMemoryOverflow()
    {
        $mem = memory_get_usage();
        if ($mem > ($this->_memory * 1024 * 1024)) {
            return true;
        }
        return false;
    }

    /**
     * Set Error Message
     *
     * @param string $error
     * @return void
     */
    public function setError($error)
    {
        $this->_error = $error;
    }

    /**
     * Get Error Message
     *
     * @return string|null
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Set Job Workload
     *
     * @param mixed
     * @return void
     */
    public function setWorkload($workload)
    {
        $this->_workload = $workload;
    }

    /**
     * Get Job Workload
     *
     * @return mixed
     */
    public function getWorkload()
    {
        return $this->_workload;
    }

    /**
     * Work, work, work
     *
     * @return mixed
     */
    public final function work($job)
    {
        $this->setWorkload($job->workload());
        return $this->_work();
    }
}
