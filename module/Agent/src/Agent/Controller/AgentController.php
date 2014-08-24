<?php

namespace Agent\Controller;

use SebastianBergmann\Exporter\Exception;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Config\Config;
use Zend\Http\Response;

use Agent\ConfigAwareInterface;
use Agent\Deploy\Adapter\Tarball;
use Agent\Service\FileSystem;


class AgentController extends AbstractActionController implements ConfigAwareInterface
{
    protected $config;

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function indexAction()
    {
        $config = new Config($this->getConfig());
        $buildId = $this->getRequest()->getPost('buildId');
        $url = $this->getRequest()->getPost('packageUrl');
        $logger = $this->createLogger($config->destPath);
        try {
            $logger->info('Downloading archive');
            $tarball = new Tarball($url, $config->destPath);
            $logger->info('Downloading archive [done]');

            $logger->info('Extraction');
            if ($tarball->extract())
                $logger->info('Extraction [done]'); /**/
            else
                throw new Exception('Extraction failed.');

        } catch (Exception $e) {
            $logger->err("An error has occurs during the deployment.");
            $logger->err("Details:" . $e->getMessage());
        }

        $logger->info('Delete temporary files');
        if (!is_null($tarball))
            $tarball->cleanTemporaryFile();
        $logger->info('Delete temporary files [done]');

        return new ViewModel(array());
    }

    private function createLogger($filePath)
    {
        /** @todo: create logger object */
        $logger = new Logger();
        FileSystem::mkdirp($filePath, 0777, true);
        $writer = new Stream($filePath . 'deployment.log');
        $logger->addWriter($writer);
        $logger->info('######## START DEPLOYMENT ########');
        return $logger;
    }
}
