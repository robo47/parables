<?php
/**
 * Zend_Loader_Autoloader
 */
require_once 'Zend/Loader/Autoloader.php';

class Zend_Application_Resource_DoctypeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // Store original autoloaders
        $this->loaders = spl_autoload_functions();
        if (!is_array($this->loaders)) {
            // spl_autoload_functions does not return empty array when no
            // autoloaders registered...
            $this->loaders = array();
        }

        Zend_Loader_Autoloader::resetInstance();
        $this->autoloader = Zend_Loader_Autoloader::getInstance();
        $this->autoloader->setFallbackAutoloader(true);

        $this->application = new Zend_Application('testing');
        $this->bootstrap = new Zend_Application_Bootstrap_Bootstrap($this->application);
        Zend_Controller_Front::getInstance()->resetInstance();
    }

    public function tearDown()
    {
        // Restore original autoloaders
        $loaders = spl_autoload_functions();
        foreach ($loaders as $loader) {
            spl_autoload_unregister($loader);
        }

        foreach ($this->loaders as $loader) {
            spl_autoload_register($loader);
        }

        // Reset autoloader instance so it doesn't affect other tests
        Zend_Loader_Autoloader::resetInstance();
    }

    /*
     * @todo Determine how to test whether the view helper was properly 
     * initialized
    public function testInitializationInitializesViewHelper()
    {
        $viewResource = new Zend_Application_Resource_View(array());
        $viewResource->setBootstrap($this->bootstrap);
        $viewResource->init();

        $doctypeResource = new Parables_Application_Resource_Doctype(array('type' => 'XHTML1_STRICT'));
        $doctypeResource->setBootstrap($this->bootstrap);
        $doctypeResource->init();

        $this->assertEquals('XHTML1_STRICT', $viewResource->doctype()->getDoctype());
    }
     */

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testPassingUndefinedDoctypeShouldRaiseException()
    {
        $resource = new Parables_Application_Resource_Doctype(array());
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }
}
