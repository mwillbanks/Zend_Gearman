<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /**
     * Initialize GearmanWorker
     *
     * @return GearmanWorker
     */
    protected function _initGearmanWorker()
    {
        $options = $this->getOptions();
        $gearmanworker = new GearmanWorker();
        if (isset($options['gearmanworker']) && isset($options['gearmanworker']['servers'])) {
            $gearmanworker->addServers($options['gearmanworker']['servers']);
        } else {
            $gearmanworker->addServer();
        }
        return $gearmanworker;
    }

    /**
     * Run
     * Handles booting up the worker and running it.
     *
     * @return void
     */
    public function run()
    {
        global $argv;
        if (!isset($argv[1])) {
            throw new InvalidArgumentException('A Worker Name Must Be Passed In');
        }
        $worker = ucwords(basename($argv[1]));
        $workerName = $worker . 'Worker';
        $workerFile = APPLICATION_PATH . '/workers/' . $workerName . '.php';

        if (!file_exists($workerFile)) {
            throw new InvalidArgumentException('The worker file does not exist: ' . $workerFile);
        }
        require $workerFile;
        if (!class_exists($workerName)) {
            throw new InvalidArgumentException('The worker class: ' . $workerName . ' does not exist in file: ' . $workerFile);
        }
        $worker = new $workerName($this);
    }
}
