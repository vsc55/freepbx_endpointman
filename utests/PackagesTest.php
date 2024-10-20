<?php
use PHPUnit\Framework\TestCase;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class PackagesTest extends TestCase
{
    protected static $f;
    protected static $o;
    protected static $module = 'Endpointman';
    
    public static function setUpBeforeClass(): void
    {
        self::$f = FreePBX::create();
        self::$o = self::$f->Endpointman;
    }

    public function testCreateEndpointman()
    {
        $this->assertTrue(is_object(self::$o), sprintf("Did not get a %s object", self::$module));
    }

    
    // public function testPackageBrandUpdateCheckAll()
    // {
    //     $check = self::$o->epm_config->brand_update_check_all();
    // }

    public function testPackageBrandUpdateCheck()
    {
        $check = self::$o->epm_config->update_check();
        $this->assertTrue(is_array($check));
    }

    public function testPackageBrandDownload()
    {
        $result = self::$o->epm_config->download_brand(1, false);
        $this->assertTrue($result);
    }

    public function testPackageBrandRemove()
    {
        $result = self::$o->epm_config->remove_brand(1, true, false);
        $this->assertTrue($result);

    }
}