<?php
/**
 * @copyright Dong <ddliuhb@gmail.com>
 * @licence http://maxmars.net/license/MIT
 */

class ConfigTest extends PHPUnit_Framework_TestCase{
    protected $config;

    public function setup()
    {
        $this->config = new ArkConfig(array(
            'database' => array(
                'host' => 'localhost',
                'username' => 'dong',
                'password' => 'pass',
            ),
            'users' => array(
                'user1',
                'user2',
                'user3',
            ),
            'deep1' => array(
                'deep2' => array(
                    'deep3' => array(
                        'deep4' => 'value4'
                    )
                )
            )
        ));
    }
    
    public function testCommon()
    {
        //get
        $this->assertEquals($this->config->get('database.host'), 'localhost');

        //get with default
        $this->assertEquals($this->config->get('database.nonexists', 5), 5);

        //set
        $this->config->set('database.port', 1234);
        $this->assertEquals($this->config->get('database.port'), 1234);

        //has
        $this->assertTrue($this->config->has('database.password'));

        //remove
        $this->config->remove('database.username');
        $this->assertFalse($this->config->has('database.username'));


    }

    public function testMerge()
    {
        //simple merge
        $this->config->merge('database', array(
            'driver' => 'sqlite',
            'username' => 'root',
        ));

        $this->assertEquals($this->config->get('database.driver'), 'sqlite');
        $this->assertEquals($this->config->get('database.username'), 'root');
        $this->assertTrue($this->config->has('database.host'));

        $this->config->merge('users', 'user4');
        $this->assertContains('user4', $this->config->get('users'));

        //deep merge
        $this->config->merge('deep1.deep2', array(
            'deep3' => array(
                'deep4a' => 'value4a',
            ),
            'deep3a' => 'value3a',
            'deep3b',
        ));

        $this->assertEquals($this->config->get('deep1.deep2.deep3.deep4a'), 'value4a');
        $this->assertEquals($this->config->get('deep1.deep2.deep3a'), 'value3a');
        $this->assertContains('deep3b', $this->config->get('deep1.deep2'));

        //merge from top level
        $this->config->merge(null, array(
            'database' => array(
                'replication' => true,
            ),
            'another',
        ));

        $this->assertEquals($this->config->get('database.replication'), true);
        $this->assertContains('another', $this->config->get());
    }

    public function testAppend()
    {
        $this->config->append(null, 'another');
        $this->assertContains('another', $this->config->get());

        $this->config->append('users', 'userx');
        $this->assertContains('userx', $this->config->get('users'));
    }

    public function testRefference()
    {
        $this->assertFalse($this->config->has('database.driver'));
        $database = $this->config->get('database');
        $database['driver'] = 'mysql';
        $this->assertFalse($this->config->has('database.driver'));
    }
}