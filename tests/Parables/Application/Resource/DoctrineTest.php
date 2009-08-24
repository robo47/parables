<?php
/**
 * Zend_Loader_Autoloader
 */
require_once 'Zend/Loader/Autoloader.php';

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
        $this->bootstrap = new 
            Zend_Application_Bootstrap_Bootstrap($this->application);
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

    public function testPathsAreInitialized()
    {
        $currentPath = realpath(dirname((__FILE__)));

        $options = array(
            'paths' => array(
                'test' => array(
                    'data_fixtures_path'    => $currentPath . '/../_files/doctrine/data/fixtures',
                    'migrations_path'       => $currentPath . '/../_files/doctrine/migrations',
                    'models_path'           => $currentPath . '/../_files/doctrine/models',
                    'models_generated_path' => $currentPath . '/../_files/doctrine/models/generated',
                    'sql_path'              => $currentPath . '/../_files/doctrine/data/sql',
                    'yaml_schema_path'      => $currentPath . '/../_files/doctrine/schema',
                )
            )
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $values = $resource->init();
        $this->assertArrayHasKey('paths', $values);
        // $this->assertArrayHasKey('test', $values['paths']);
        // $this->assertArrayHasKey('data_fixtures_path', $values['paths']['test']);
        // $this->assertArrayHasKey('migrations_path', $values['paths']['test']);
        // $this->assertArrayHasKey('models_path', $values['paths']['test']);
        // $this->assertArrayHasKey('models_generated_path', $values['paths']['test']);
        // $this->assertArrayHasKey('sql_path', $values['paths']['test']);
        // $this->assertArrayHasKey('yaml_schema_path', $values['paths']['test']);
    }

    public function testManagerIsInitialized()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'attr_auto_accessor_override' => 1,
                    'attr_auto_free_query_objects' => 1,
                    // 'attr_autoload_table_classes' => 1,
                    // 'attr_quote_identifier' => 1,
                    // 'attr_use_dql_callbacks' => 1,
                    // 'attr_use_native_enum' => 0,
                    // 'attr_query_limit' => 0,
                    //
                    // 'attr_dbname_format' => 'appname_%s',
                    //
                    // 'attr_default_column_options' => array(
                    //      'length' => 255,
                    //      'notnull' => 1,
                    //      'type' => 'string',
                    //  ),
                    //
                    // 'attr_default_identifier_options => array(
                    //      'length' => 16,
                    //      'name' => '%s_id',
                    //      'type' => 'string',
                    //  ),
                    //
                    // 'attr_idxname_format' => '%s_index',
                    // 'attr_seqname_format' => '%s_sequence',
                    // 'attr_tblname_format' => '%s',
                    //
                    'attr_export' => 'export_all',
                    'attr_model_loading' => 'model_loading_conservative',
                    'attr_portability' => 'portability_all',
                    'attr_validate' => 'validate_all',
                )
            )
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $reflect = new ReflectionClass('Doctrine');
        $doctrineConstants = $reflect->getConstants();

        $manager = $resource->getManager();
        foreach ($options['manager']['attributes'] as $key => $value) {
            $attrIdx = $doctrineConstants[strtoupper($key)];
            $this->assertEquals($value, $manager->getAttribute($attrIdx));
        }
    }

    public function testConnectionsAreInitialized()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'attributes' => array(
                        'attr_auto_accessor_override' => 1,
                        'attr_auto_free_query_objects' => 1,
                        // 'attr_autoload_table_classes' => 1,
                        // 'attr_quote_identifier' => 1,
                        // 'attr_use_dql_callbacks' => 1,
                        // 'attr_use_native_enum' => 0,
                        // 'attr_query_limit' => 0,
                        //
                        // 'attr_dbname_format' => 'appname_%s',
                        //
                        // 'attr_default_column_options' => array(
                        //      'length' => 255,
                        //      'notnull' => 1,
                        //      'type' => 'string',
                        //  ),
                        //
                        // 'attr_default_identifier_options => array(
                        //      'length' => 16,
                        //      'name' => '%s_id',
                        //      'type' => 'string',
                        //  ),
                        //
                        // 'attr_idxname_format' => '%s_index',
                        // 'attr_seqname_format' => '%s_sequence',
                        // 'attr_tblname_format' => '%s',
                        //
                        'attr_export' => 'export_all',
                        'attr_model_loading' => 'model_loading_conservative',
                        'attr_portability' => 'portability_all',
                        'attr_validate' => 'validate_all',
                    )
                )
            )
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();

        $manager = $resource->getManager();

        $connections = $manager->getConnections();
        foreach ($connections as $conn) {
            $this->assertTrue($conn instanceof Doctrine_Connection_Common);
        }

        $reflect = new ReflectionClass('Doctrine');
        $doctrineConstants = $reflect->getConstants();

        foreach ($options['connections']['demo']['attributes'] as $key => $value) {
            $attrIdx = $doctrineConstants[strtoupper($key)];
            $this->assertEquals($value, $manager->getAttribute($attrIdx));
        }
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testMissingDsnException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
            )
        ));

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testInvalidManagerAttributeException()
    {
        $options = array(
            'manager' => array(
                'attributes' => array(
                    'invalid' => 1,
                )
            )
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testInvalidConnectionAttributeException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'attributes' => array(
                        'invalid' => 1,
                    )
                )
            )
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
     */
    public function testMissingCacheClassException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'attributes' => array(
                        'attr_query_cache' => array(
                            // 'class' => 'Doctrine_Cache_Memcache',
                            'lifespan' => 3600,
                            'options' => array(
                                'servers' => array(
                                    'host' => 'localhost',
                                    'port' => '11211',
                                    'persistent' => 1
                                ),
                                'compression' => 0,
                            )
                        )
                    )
                )
            )
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }

    /**
     * @expectedException Zend_Application_Resource_Exception
    public function testCacheClassDoesNotExistException()
    {
        $options = array(
            'connections' => array(
                'demo' => array(
                    'dsn' => 'sqlite:///' . realpath(__FILE__) . '/../../_files/test.db',
                    'attributes' => array(
                        'attr_query_cache' => array(
                            'class' => 'Invalid',
                            'lifespan' => 3600,
                            'options' => array(
                                'servers' => array(
                                    'host' => 'localhost',
                                    'port' => '11211',
                                    'persistent' => 1
                                ),
                                'compression' => 0,
                            )
                        )
                    )
                )
            )
        );

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }
     */

    /**
     * @expectedException Zend_Application_Resource_Exception
    public function testInvalidListenerClassException()
    {
        $options = array(
        ));

        $resource = new Parables_Application_Resource_Doctrine($options);
        $resource->setBootstrap($this->bootstrap);
        $resource->init();
    }
     */
}
