<?php

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '../../TestHelper.php';


class Parables_Doctrine_EventListener_CharsetTest extends PHPUnit_Framework_TestCase
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
		if (method_exists('Doctrine_Manager', 'resetInstance')) // as of 1.2ALPHA3
			Doctrine_Manager::resetInstance();

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

    /**
     * @covers Parables_Doctrine_EventListener_Charset::__construct
     */
    public function testConstructDefault()
    {
        $listener = new Parables_Doctrine_EventListener_Charset();
        $this->assertEquals('utf-8', $listener->getCharset());
    }
    /**
     * @covers Parables_Doctrine_EventListener_Charset::__construct
     */
    public function testConstructWithCharset()
    {
        $listener = new Parables_Doctrine_EventListener_Charset('something');
        $this->assertEquals('something', $listener->getCharset());
    }

    /**
     * @covers Parables_Doctrine_EventListener_Charset::setCharset
     * @covers Parables_Doctrine_EventListener_Charset::getCharset
     */
    public function testSetCharsetGetCharset()
    {
        $listener = new Parables_Doctrine_EventListener_Charset();
        $listener->setCharset('something');
        $this->assertEquals('something', $listener->getCharset());
    }

    /**
     * @covers Parables_Doctrine_EventListener_Charset::setCharset
     */
    public function testSetCharsetUsesFluentInterface()
    {
        $listener = new Parables_Doctrine_EventListener_Charset();
        $return = $listener->setCharset('something');
        $this->assertSame($listener, $return, 'setCharset doesn\'t return $this (Fluent Interface)');
    }

    /**
     * This unittest will fail on a mysql/pgsql-connection because of
     * http://www.doctrine-project.org/jira/browse/DC-434
     *
     * @covers Parables_Doctrine_EventListener_Charset::postConnect
     */
    public function testSettingCharsetOnConnectionAfterConnect()
    {
        $conn = Doctrine_Manager::getInstance()->connection('sqlite::memory:', 'demo');

        $this->assertEquals(null, $conn->getCharset(), 'Charset already has non-null value');
        $this->assertFalse($conn->isConnected(), 'Connection already opened');

        $listener = new Parables_Doctrine_EventListener_Charset('latin1');
        $conn->addListener($listener);

        $this->assertEquals(null, $conn->getCharset(), 'Charset already has non-null value');
        $this->assertFalse($conn->isConnected(), 'Connection already opened');
        
        $conn->connect();

        $this->assertTrue($conn->isConnected(), 'Connection not opened');

        $this->assertEquals('latin1', $conn->getCharset(), 'charset was not set by EventListener');
    }
}
