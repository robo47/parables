<?php

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '../../TestHelper.php';

class Parables_Cache_DoctrineDummy extends Doctrine_Cache_Driver
{
    public $args = array();

    public function __construct()
    {
        $this->args = func_get_args();
    }

    protected function _doFetch($id, $testCacheValidity = true) {}

    protected function _doContains($id) {}

    protected function _doSave($id, $data, $lifeTime = false) {}

    protected function _doDelete($id) {}
}

class Parables_Doctrine_EventListenerDummy extends Doctrine_EventListener
{
    public $args = array();

    public function __construct()
    {
        $this->args = func_get_args();
    }
}

class Parables_Doctrine_Record_ListenerDummy extends Doctrine_Record_Listener
{
    public $args = array();

    public function __construct()
    {
        $this->args = func_get_args();
    }
}



class Zend_Application_Resource_DoctrineTest extends PHPUnit_Framework_TestCase
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
     * @covers Parables_Application_Resource_Doctrine::init
     */
    public function testInitializationReturnsParablesApplicationResourceDoctrine()
    {
        $options = array();
        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $doctrine = $resource->init();

        $this->assertThat(
            $doctrine,
            $this->isInstanceOf('Parables_Application_Resource_Doctrine')
        );
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::init
     * @covers Parables_Application_Resource_Doctrine::setupManager
     * @covers Parables_Application_Resource_Doctrine::_setAttributes
     */
    public function testInitializationInitializesManager()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_auto_accessor_override' => false,
                    'attr_auto_free_query_objects' => false,
                    'attr_autoload_table_classes' => false,
                    // 'attr_cascade_saves' => true,
                    // 'attr_collection_class' => 'Doctrine_Collection',
                    // 'attr_create_tables' => true,
                    'attr_decimal_places' => 2,
                    'attr_default_column_options' => array(
                        'type' => 'string',
                        'length' => 255,
                        'notnull' => true,
                    ),
                    'attr_default_identifier_options' => array(
                        'name' => '%s_id',
                        'type' => 'string',
                        'length' => 16,
                    ),
                    'attr_default_param_namespace' => 'doctrine',
                    // 'attr_def_text_length' => 4096,
                    // 'attr_def_varchar_length' => 255,
                    'attr_emulate_database' => false,
                    'attr_export' => 'export_all',
                    'attr_fkname_format' => "%s",
                    'attr_hydrate_overwrite' => true,
                    'attr_idxname_format' => "%s_idx",
                    'attr_load_references' => true,
                    'attr_model_loading' => 'model_loading_conservative',
                    'attr_portability' => 'portability_none',
                    // 'attr_query_class' => 'Doctrine_Query',
                    'attr_query_limit' => 'limit_records',
                    'attr_quote_identifier' => false,
                    'attr_recursive_merge_fixtures' => false,
                    'attr_seqcol_name' => 'id',
                    'attr_seqname_format' => "%s_seq",
                    // 'attr_table_class' => 'Doctrine_Table',
                    // 'attr_tblclass_format' => "",
                    'attr_tblname_format' => "%s",
                    'attr_throw_exceptions' => true,
                    'attr_use_dql_callbacks' => false,
                    'attr_use_native_enum' => false,
                    'attr_validate' => 'validate_none',
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $reflect = new ReflectionClass('Doctrine');
        $doctrineConstants = $reflect->getConstants();

        $manager = Doctrine_Manager::getInstance();
        foreach ($options['manager']['attributes'] as $key => $value) {
            $attrIdx = $doctrineConstants[strtoupper($key)];
            $attrVal = $value;

            if (is_string($value)) {
                $value = strtoupper($value);
                if (array_key_exists($value, $doctrineConstants)) {
                    $attrVal = $doctrineConstants[$value];
                }
            }

            $this->assertEquals($attrVal, $manager->getAttribute($attrIdx));
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_setAttributes
     */
    public function testPassingInvalidManagerAttributeShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'invalid' => 1,
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals("Invalid attribute INVALID.", $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerApcQueryCache()
    {
        if (extension_loaded('apc')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'driver' => 'apc',
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Apc')
            );
        } else {
            $this->markTestSkipped('Extension APC not available');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerDbQueryCache()
    {
        if (extension_loaded('sqlite')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'driver' => 'db',
                            'options' => array(
                                'dsn' => 'sqlite::memory:',
                                'tableName' => 'doctrine_query_cache',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Db')
            );
        } else {
            $this->markTestSkipped('Extension Sqlite not available');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerMemcacheQueryCache()
    {
        if (extension_loaded('memcache')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'driver' => 'memcache',
                            'options' => array(
                                'servers' => array(
                                    'host' => 'localhost',
                                    'port' => 11211,
                                    'persistent' => true,
                                ),
                                'compression' => false,
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Memcache')
            );
        } else {
            $this->markTestSkipped('Extension Memcache not available');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerXcacheQueryCache()
    {
        if (extension_loaded('xcache')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'driver' => 'xcache',
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Xcache')
            );
        } else {
            $this->markTestSkipped('Extension XCache not available');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerApcResultCache()
    {
        if (extension_loaded('apc')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_result_cache' => array(
                            'driver' => 'apc',
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Apc')
            );
        } else {
            $this->markTestSkipped('Extension APC not available');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerDbResultCache()
    {
        if (extension_loaded('sqlite')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_result_cache' => array(
                            'driver' => 'db',
                            'options' => array(
                                'dsn' => 'sqlite::memory:',
                                'tableName' => 'doctrine_result_cache',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Db')
            );
        } else {
            $this->markTestSkipped('Extension Sqlite not available');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerMemcacheResultCache()
    {
        if (extension_loaded('memcache')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_result_cache' => array(
                            'driver' => 'memcache',
                            'options' => array(
                                'servers' => array(
                                    'host' => 'localhost',
                                    'port' => 11211,
                                    'persistent' => true,
                                ),
                                'compression' => false,
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Memcache')
            );
        } else {
            $this->markTestSkipped('Extension memcache not available');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerXcacheResultCache()
    {
        if (extension_loaded('xcache')) {
            $options = array(
                'manager' => array(
                    'attributes' => array(
                        'attr_result_cache' => array(
                            'driver' => 'xcache',
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $cache = $manager->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Xcache')
            );
        } else {
            $this->markTestSkipped('Extension XCache not available');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testPassingUndefinedCacheDriverShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Undefined cache driver.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testPassingUnsupportedCacheDriverShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'array',
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Unsupported cache driver: array', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testPassingUndefinedDbCacheOptionsShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Undefined db cache options.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testPassingInvalidDbCacheOptionsShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                        'options' => array(),
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Invalid db cache options.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testPassingUndefinedDbCacheDsnShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                        'options' => array(
                            'tableName' => 'doctrine_cache',
                        ),
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Undefined db cache DSN.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testPassingInvalidDbCacheDsnShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                        'options' => array(
                            'dsn' => '',
                        ),
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Invalid db cache DSN.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testPassingUndefinedDbCacheTableNameShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                        'options' => array(
                            'dsn' => 'sqlite::memory:',
                        ),
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Undefined db cache table name.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testPassingInvalidDbCacheTableNameShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'db',
                        'options' => array(
                            'dsn' => 'sqlite::memory:',
                            'tableName' => '',
                        ),
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Invalid db cache table name.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testPassingUndefinedMemcacheOptionsShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'memcache',
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Undefined memcache options.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testPassingInvalidMemcacheOptionsShouldRaiseException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'memcache',
                        'options' => array(),
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Invalid memcache options.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::init
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testInitializationInitializesConnections()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'attributes' => array(
                        'attr_auto_accessor_override' => false,
                        'attr_auto_free_query_objects' => false,
                        'attr_autoload_table_classes' => false,
                        // 'attr_cascade_saves' => true,
                        // 'attr_collection_class' => 'Doctrine_Collection',
                        // 'attr_create_tables' => true,
                        'attr_decimal_places' => 2,
                        'attr_default_column_options' => array(
                            'type' => 'string',
                            'length' => 255,
                            'notnull' => true,
                        ),
                        'attr_default_identifier_options' => array(
                            'name' => '%s_id',
                            'type' => 'string',
                            'length' => 16,
                        ),
                        'attr_default_param_namespace' => 'doctrine',
                        // 'attr_def_text_length' => 4096,
                        // 'attr_def_varchar_length' => 255,
                        'attr_emulate_database' => false,
                        'attr_export' => 'export_all',
                        'attr_fkname_format' => "%s",
                        'attr_hydrate_overwrite' => true,
                        'attr_idxname_format' => "%s_idx",
                        'attr_load_references' => true,
                        'attr_model_loading' => 'model_loading_conservative',
                        'attr_portability' => 'portability_none',
                        // 'attr_query_class' => 'Doctrine_Query',
                        'attr_query_limit' => 'limit_records',
                        'attr_quote_identifier' => false,
                        'attr_recursive_merge_fixtures' => false,
                        'attr_seqcol_name' => 'id',
                        'attr_seqname_format' => "%s_seq",
                        // 'attr_table_class' => 'Doctrine_Table',
                        // 'attr_tblclass_format' => "",
                        'attr_tblname_format' => "%s",
                        'attr_throw_exceptions' => true,
                        'attr_use_dql_callbacks' => false,
                        'attr_use_native_enum' => false,
                        'attr_validate' => 'validate_none',
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');

        $reflect = new ReflectionClass('Doctrine');
        $doctrineConstants = $reflect->getConstants();

        foreach ($options['connections']['demo']['attributes'] as $key => $value) {
            $attrIdx = $doctrineConstants[strtoupper($key)];
            $attrVal = $value;

            if (is_string($value)) {
                $value = strtoupper($value);
                if (array_key_exists($value, $doctrineConstants)) {
                    $attrVal = $doctrineConstants[$value];
                }
            }

            $this->assertEquals($attrVal, $conn->getAttribute($attrIdx));
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testInitializationInitializesConnectionApcQueryCache()
    {
        if (extension_loaded('apc')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                        'attributes' => array(
                            'attr_query_cache' => array(
                                'driver' => 'apc',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Apc')
            );
        }
    }
    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testInitializationInitializesConnectionDbQueryCache()
    {
        if (extension_loaded('sqlite')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                        'attributes' => array(
                            'attr_query_cache' => array(
                                'driver' => 'db',
                                'options' => array(
                                    'dsn' => 'sqlite::memory:',
                                    'tableName' => 'doctrine_query_cache',
                                ),
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Db')
            );
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testInitializationInitializesConnectionMemcacheQueryCache()
    {
        if (extension_loaded('memcache')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                        'attributes' => array(
                            'attr_query_cache' => array(
                                'driver' => 'memcache',
                                'options' => array(
                                    'servers' => array(
                                        'host' => 'localhost',
                                        'port' => 11211,
                                        'persistent' => true,
                                    ),
                                    'compression' => false,
                                ),
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Memcache')
            );
        }
    }
    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testInitializationInitializesConnectionXcacheQueryCache()
    {
        if (extension_loaded('xcache')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                        'attributes' => array(
                            'attr_query_cache' => array(
                                'driver' => 'xcache',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_QUERY_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Xcache')
            );
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testInitializationInitializesConnectionApcResultCache()
    {
        if (extension_loaded('apc')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                        'attributes' => array(
                            'attr_result_cache' => array(
                                'driver' => 'apc',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Apc')
            );
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testInitializationInitializesConnectionDbResultCache()
    {
        if (extension_loaded('sqlite')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                        'attributes' => array(
                            'attr_result_cache' => array(
                                'driver' => 'db',
                                'options' => array(
                                    'dsn' => 'sqlite::memory:',
                                    'tableName' => 'doctrine_result_cache',
                                ),
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Db')
            );
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testInitializationInitializesConnectionMemcacheResultCache()
    {
        if (extension_loaded('memcache')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                        'attributes' => array(
                            'attr_result_cache' => array(
                                'driver' => 'memcache',
                                'options' => array(
                                    'servers' => array(
                                        'host' => 'localhost',
                                        'port' => 11211,
                                        'persistent' => true,
                                    ),
                                    'compression' => false,
                                ),
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Memcache')
            );
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_getCache
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testInitializationInitializesConnectionXcacheResultCache()
    {
        if (extension_loaded('xcache')) {
            $options = array(
                'connections' => array(
                    'demo' => array(
                        'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                        'attributes' => array(
                            'attr_result_cache' => array(
                                'driver' => 'xcache',
                            ),
                        ),
                    ),
                ),
            );

            $resource = new Parables_Application_Resource_Doctrine($options);
            $resource->setBootstrap($this->bootstrap);
            $resource->init();

            $manager = Doctrine_Manager::getInstance();
            $conn = $manager->getConnection('demo');
            $cache = $conn->getAttribute(Doctrine::ATTR_RESULT_CACHE);

            $this->assertThat(
                $cache,
                $this->isInstanceOf('Doctrine_Cache_Xcache')
            );
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_setAttributes
     */
    public function testPassingInvalidConnectionAttributeShouldRaiseException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'attributes' => array(
                        'invalid' => 1,
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Invalid attribute INVALID.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testPassingUndefinedDsnShouldRaiseException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Undefined DSN on demo.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testPassingInvalidDsnShouldRaiseException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => '',
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Invalid DSN on demo.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /*
    public function testInitializationInitializesPaths()
    {
        $options = array(
            'paths' => array(
                'demo' => array(
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $paths = $resource->getPaths();

        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $paths);
        $this->assertArrayHasKey('demo', $paths);
    }
    */

    /**
     * @expectedException Zend_Application_Resource_Exception
    public function testPassingInvalidPathsShouldRaiseException()
    {
        $options = array(
            'paths' => array(
                'demo' => null,
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }
     */

    /**
     * @covers Parables_Application_Resource_Doctrine::setupPaths
     */
    public function testPassingNonexistentPathShouldRaiseException()
    {
        $options = array(
            'paths' => array(
                'demo' => array(
                    'somekey' => 'somepath',
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('somepath does not exist.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupPaths
     */
    public function testPassingCorrectPathOptionsAndGettingThem()
    {
        $options = array(
            'paths' => array(
                'demo' => array(
                    'somekey' => dirname(__FILE__),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $paths = $resource->getPaths();

        $this->assertEquals($options['paths'], $paths);
    }


    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     * @covers Parables_Application_Resource_Doctrine::_setConnectionListeners
     * @covers Parables_Application_Resource_Doctrine::_getListenerInstance
     */
    public function testPassingCorrectListener()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'listeners' => array(
                        'dummy' => 'Parables_Doctrine_EventListenerDummy',
                    ),
                ),
            ),
        );
        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');
        $listener = $conn->getListener();

        $this->assertType('Parables_Doctrine_EventListenerDummy', $listener->get('dummy'));
        $this->assertEquals(array(), $listener->get('dummy')->args);
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     * @covers Parables_Application_Resource_Doctrine::_setConnectionListeners
     * @covers Parables_Application_Resource_Doctrine::_getListenerInstance
     */
    public function testPassingCorrectListenerWithParams()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'listeners' => array(
                        'dummy' => array(
                            'Parables_Doctrine_EventListenerDummy',
                            array('param1', 'param2')
                        ),
                    ),
                ),
            ),
        );
        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');
        $listener = $conn->getListener();

        $this->assertType('Parables_Doctrine_EventListenerDummy', $listener->get('dummy'));
        $this->assertEquals(array('param1', 'param2'), $listener->get('dummy')->args);
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     * @covers Parables_Application_Resource_Doctrine::_setConnectionRecordListeners
     * @covers Parables_Application_Resource_Doctrine::_getListenerInstance
     */
    public function testPassingCorrectRecordListener()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'recordListeners' => array(
                        'dummy' => 'Parables_Doctrine_Record_ListenerDummy',
                    ),
                ),
            ),
        );
        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');
        $listener = $conn->getRecordListener();

        $this->assertType('Parables_Doctrine_Record_ListenerDummy', $listener->get('dummy'));
        $this->assertEquals(array(), $listener->get('dummy')->args);
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     * @covers Parables_Application_Resource_Doctrine::_setConnectionRecordListeners
     * @covers Parables_Application_Resource_Doctrine::_getListenerInstance
     */
    public function testPassingCorrectRecordListenerWithParams()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'recordListeners' => array(
                        'dummy' => array(
                            'Parables_Doctrine_Record_ListenerDummy',
                            array('param1', 'param2')
                        ),
                    ),
                ),
            ),
        );
        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');
        $listener = $conn->getRecordListener();

        $this->assertType('Parables_Doctrine_Record_ListenerDummy', $listener->get('dummy'));
        $this->assertEquals(array('param1', 'param2'), $listener->get('dummy')->args);
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_setConnectionListeners
     * @covers Parables_Application_Resource_Doctrine::_getListenerInstance
     * @covers Parables_Application_Resource_Doctrine::_getListenerInstance
     */
    public function testPassingInvalidListenerClassShouldRaiseException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'listeners' => array(
                        'profiler' => 'Invalid_Listener',
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Invalid_Listener does not exist.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::_setConnectionRecordListeners
     * @covers Parables_Application_Resource_Doctrine::_getListenerInstance
     * @covers Parables_Application_Resource_Doctrine::_getListenerInstance
     */
    public function testPassingInvalidRecordListenerClassShouldRaiseException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'recordListeners' => array(
                        'listener' => 'Invalid_Listener',
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('Invalid_Listener does not exist.', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupManager
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerSelfWrittenQueryCache()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'Parables_Cache_DoctrineDummy',
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $cache = $manager->getAttribute(Doctrine::ATTR_QUERY_CACHE);

        $this->assertThat(
            $cache,
            $this->isInstanceOf('Parables_Cache_DoctrineDummy')
        );
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupManager
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerSelfWrittenQueryCacheWithOptions()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_query_cache' => array(
                        'driver' => 'Parables_Cache_DoctrineDummy',
                        'options' => array(
                            'val1',
                            'val2',
                            )
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $cache = $manager->getAttribute(Doctrine::ATTR_QUERY_CACHE);

        $this->assertThat(
            $cache,
            $this->isInstanceOf('Parables_Cache_DoctrineDummy')
        );

        $expectedOptions = $options['manager']['attributes']['attr_query_cache']['options'];

        $this->assertEquals($expectedOptions, $cache->args, 'Options passed to Cache constructor don\'t match');
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerSelfWrittenResultCache()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_result_cache' => array(
                        'driver' => 'Parables_Cache_DoctrineDummy',
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $cache = $manager->getAttribute(Doctrine::ATTR_RESULT_CACHE);

        $this->assertThat(
            $cache,
            $this->isInstanceOf('Parables_Cache_DoctrineDummy')
        );
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupManager
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesManagerSelfWrittenResultCacheWithOptions()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_result_cache' => array(
                        'driver' => 'Parables_Cache_DoctrineDummy',
                        'options' => array(
                            'val1',
                            'val2',
                            )
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $cache = $manager->getAttribute(Doctrine::ATTR_RESULT_CACHE);

        $this->assertThat(
            $cache,
            $this->isInstanceOf('Parables_Cache_DoctrineDummy')
        );

        $expectedOptions = $options['manager']['attributes']['attr_result_cache']['options'];

        $this->assertEquals($expectedOptions, $cache->args, 'Options passed to Cache constructor don\'t match');
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     */
    public function testSettingCharsetAddsTheCharsetListener()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'charset' => 'something',
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');
        $listener = $conn->getListener();

        $this->assertType('Doctrine_EventListener_Chain', $listener);
        $this->assertType('Parables_Doctrine_EventListener_Charset', $listener->get('charset'));
        $this->assertEquals('something', $listener->get('charset')->getCharset(), 'Charset is wrong');
    }


    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesConnectionSelfWrittenQueryCache()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'driver' => 'Parables_Cache_DoctrineDummy',
                          ),
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');
        $cache = $conn->getAttribute(Doctrine::ATTR_QUERY_CACHE);

        $this->assertThat(
            $cache,
            $this->isInstanceOf('Parables_Cache_DoctrineDummy')
        );
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesConnectionSelfWrittenQueryCacheWithOptions()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'driver' => 'Parables_Cache_DoctrineDummy',
                            'options' => array(
                                'val1',
                                'val2',
                                ),
                          ),
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');
        $cache = $conn->getAttribute(Doctrine::ATTR_QUERY_CACHE);

        $this->assertThat(
            $cache,
            $this->isInstanceOf('Parables_Cache_DoctrineDummy')
        );

        $expectedOptions = $options['connections']['demo']['attributes']['attr_query_cache']['options'];

        $this->assertEquals($expectedOptions, $cache->args, 'Options passed to Cache constructor don\'t match');
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesConnectionSelfWrittenResultCache()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'attributes' => array(
                        'attr_result_cache' => array(
                            'driver' => 'Parables_Cache_DoctrineDummy',
                          ),
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');
        $cache = $conn->getAttribute(Doctrine::ATTR_RESULT_CACHE);

        $this->assertThat(
            $cache,
            $this->isInstanceOf('Parables_Cache_DoctrineDummy')
        );
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupConnections
     * @covers Parables_Application_Resource_Doctrine::_getCache
     */
    public function testInitializationInitializesConnectionSelfWrittenResultCacheWithOptions()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'attributes' => array(
                        'attr_result_cache' => array(
                            'driver' => 'Parables_Cache_DoctrineDummy',
                            'options' => array(
                                'val1',
                                'val2',
                                ),
                          ),
                    ),
                ),
            ),
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = Doctrine_Manager::getInstance();
        $conn = $manager->getConnection('demo');
        $cache = $conn->getAttribute(Doctrine::ATTR_RESULT_CACHE);

        $this->assertThat(
            $cache,
            $this->isInstanceOf('Parables_Cache_DoctrineDummy')
        );

        $expectedOptions = $options['connections']['demo']['attributes']['attr_result_cache']['options'];

        $this->assertEquals($expectedOptions, $cache->args, 'Options passed to Cache constructor don\'t match');
    }


    /**
     * @covers Parables_Application_Resource_Doctrine::init
     */
    public function testUsingCompiledVersion()
    {
        $options = array(
            'compiled' => dirname(dirname(__FILE__)) . '/_files/Doctrine.compiled.php',
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);


        $this->assertFalse(class_exists('Doctrine_Compiled_Test_Class', false), 'Class Doctrine_Compiled_Test_Class is already declared');

        $resource->init();

        $this->assertTrue(class_exists('Doctrine_Compiled_Test_Class', false), 'Class Doctrine_Compiled_Test_Class  is not declaed but should be');
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupCli
     * @covers Parables_Application_Resource_Doctrine::getCli
     * @covers Parables_Application_Resource_Doctrine::init
     */
    public function testInitializeDoctrineCli()
    {
        $options = array(
            'cli' => array(
                'config' => array(
                    'data_fixtures_path'    =>  TESTS_PATH . 'tmp/doctrine/data/fixtures/',
                    'models_path'           =>  TESTS_PATH . 'tmp/doctrine/',
                    'migrations_path'       =>  TESTS_PATH . 'tmp/doctrine/migrations/',
                    'sql_path'              =>  TESTS_PATH . 'tmp/doctrine/data/sql/',
                    'yaml_schema_path'      =>  TESTS_PATH . 'tmp/doctrine/schema/',
                    'generate_models_options' => array (
                        'generateBaseClasses'   =>  true,
                        'generateTableClasses'  =>  true,
                        'pearStyle'             =>  true,
                    ),
                ),
            )
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $cli = $resource->getCli();
        /* @var $cli Doctrine_Cli */
        $this->assertType('Doctrine_Cli', $cli);
        $this->assertEquals($options['cli']['config'], $cli->getConfig());
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupCli
     */
    public function testInitializeDoctrineCliWithRegisteringInTheRegistry()
    {
        $options = array(
            'cli' => array(
                'config' => array(),
                'registryKey' => 'Doctrine_Cli',
            )
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $this->assertTrue(Zend_Registry::isRegistered('Doctrine_Cli'));
        $cli = Zend_Registry::get('Doctrine_Cli');
        /* @var $cli Doctrine_Cli */
        $this->assertType('Doctrine_Cli', $cli);
        $this->assertEquals(array(), $cli->getConfig());
    }

    /**
     * @covers Parables_Application_Resource_Doctrine::setupCli
     */
    public function testPassingNoConfigForDoctrineCliShouldRaiseException()
    {
        $options = array(
            'cli' => array()
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);

        try {
            $resource->init();
            $this->fail('No exception thrown');
        } catch(Zend_Application_Resource_Exception $e) {
            $this->assertEquals('No config for cli found', $e->getMessage(), 'Thrown exception has wrong message');
        }
    }
}
