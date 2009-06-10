<?php
class My_Controller_Plugin_Profiler extends Zend_Controller_Plugin_Abstract
{
    /**
     * @var Zend_Log
     */
    protected $_logger = null;

    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        $front = Zend_Controller_Front::getInstance();
        if ($bootstrap = $front->getParam('bootstrap')) {
            if ($bootstrap->hasResource('logs')) {
                $this->_logger = $bootstrap->getResource('logs');
            }
        }

        $this->_loggerEvent('routeStartup');
    }

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $this->_loggerEvent('routeShutdown');
    }

    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->_loggerEvent('dispatchLoopStartup');
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request) 
    {
        $this->_loggerEvent('preDispatch');
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_loggerEvent('postDispatch');
    }

    public function dispatchLoopShutdown()
    {
        $this->_loggerEvent('dispatchLoopShutdown');
        $this->_logDoctrine(true);
    }

    private function _getTime()
    {
        return (microtime(true) - $_SERVER['REQUEST_TIME']);
    }

    private function _logDoctrine($enableQueries = false, $enableQueryParams = false)
    {
        $mgr = Doctrine_Manager::getInstance();
        $profilers = array();
        $queryCount = 0;
        $total = 0;

        foreach($mgr->getConnections() as $conn) {
            $listenerChain = $conn->getListener();
            if ($listenerChain instanceof Doctrine_EventListener_Chain) {
                if ($profiler = $listenerChain->get('profiler')) {
                    $profilers[] = $profiler;
                }
            }
        }

        foreach ($profilers as $profiler) {
            foreach ($profiler as $event) {
                if ($query = $event->getQuery()) {
                    $queryCount++;
                    if ($enableQueries) {
                        $this->_logger->info(($query));
                    }
                }

                if ($enableQueryParams) {
                    if ($params = $event->getParams()) {
                        foreach ($params as $param) {
                            $this->_logger->info($param);
                        }
                    }
                }

                $total += $event->getElapsedSecs();
            }
        }

        $this->_logger->info(sprintf('%d queries from %d connections in %s seconds', $queryCount, count($profilers), round($total, 4)));
    }

    private function _loggerEvent($data = '')
    {
        if (isset($this->_logger)) {
            $this->_logger->info(sprintf('%s @ %s', $data, round($this->_getTime(), 4)));
        }
    }
}
