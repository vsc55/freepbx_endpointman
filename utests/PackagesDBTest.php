<?php
use PHPUnit\Framework\TestCase;
use FreePBX\modules\Endpointman\PackagesDB;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class PackagesDBTest extends TestCase
{
    protected static $f;
    protected static $o;
    protected static $module = 'Endpointman';
    
    protected $new_brand;

    protected $brand_remove = false;

    public static function setUpBeforeClass(): void
    {
        self::$f = FreePBX::create();
        self::$o = self::$f->Endpointman;
    }

    // Create a new brand before each test
    protected function setUp(): void
    {
        $this->new_brand = [
            'id'        => 999999,
            'name'      => 'Test_Brand_'. uniqid(),
            'directory' => 'test_brand_'. uniqid()
        ];
    }
    
    // Remove the brand after each test
    protected function tearDown(): void
    {
        if ($this->brand_remove)
        {
            $brand = self::$o->packagesdb->getBrandByID($this->new_brand['id']);
            if ($brand && method_exists($brand, 'delete'))
            {
                $brand->delete();
            }
            $this->brand_remove = false;
        }
        
    }

    
    public function testCreateEndpointman()
    {
        $this->assertTrue(is_object(self::$o), sprintf("Did not get a %s object", self::$module));
    }

    
    public function testCreateBrand()
    {
        $this->brand_remove = true;

        $brandid = self::$o->packagesdb->createBrand($this->new_brand, true, false);
        $this->assertIsInt($brandid, 'Brand ID is not an integer');


        $brandExistId = self::$o->packagesdb->isBrandExist($brandid);
        $this->assertTrue($brandExistId, 'Brand does not exist');


        $brandID = self::$o->packagesdb->getBrandByID($brandid);
        $this->assertInstanceOf('FreePBX\modules\Endpointman\Provisioner\ProvisionerBrandDB', $brandID);
        $brandID = null;
        

        $brandDir = self::$o->packagesdb->getBrandByDirectory($this->new_brand['directory']);
        $this->assertInstanceOf('FreePBX\modules\Endpointman\Provisioner\ProvisionerBrandDB', $brandDir);
        $brandDir = null;
    }

    
    public function testCreateBrandException()
    {
        $this->brand_remove = true;
        try
        {
            self::$o->packagesdb->createBrand([], false, true);
            $this->fail("Expected exception was not thrown.");
        }
        catch (\Exception $e)
        {
            $this->assertTrue(true, $e->getMessage());
        }
    }

    
    public function testCreateBrandExceptionDuplicate()
    {
        $this->brand_remove = true;

        self::$o->packagesdb->createBrand($this->new_brand, false, true);
        try
        {
            self::$o->packagesdb->createBrand($this->new_brand, false, true);
            $this->fail("Expected exception was not thrown.");
        }
        catch (\Exception $e)
        {
            $this->assertTrue(true, $e->getMessage());
            // $this->markTestIncomplete("Caught expected exception: " . $e->getMessage());
        }
    }

    public function testDeleteBrand()
    {
        $this->brand_remove = true;

        $newid = self::$o->packagesdb->createBrand($this->new_brand, true, true);
        $brand = self::$o->packagesdb->getBrandByID($newid);
        $this->assertInstanceOf('FreePBX\modules\Endpointman\Provisioner\ProvisionerBrandDB', $brand);

        if ($brand)
        {
            $brandRemove = $brand->delete();
            $this->assertTrue($brandRemove, 'Brand not removed');
        }
    }

    public function testGetAllBrands()
    {
        $brands = self::$o->packagesdb->getBrands(true);
        $count = count($brands);
        $this->assertIsArray($brands);
        $this->assertGreaterThan(0, $count);
    }

    public function testGetAllBrandsOrderBy()
    {
        $brands = self::$o->packagesdb->getBrands(true, 'id', 'ASC');
        $count = count($brands);
        $this->assertIsArray($brands);
        $this->assertGreaterThan(0, $count);
    }

    public function testGetAllBrandsOrderByName()
    {
        $brands = self::$o->packagesdb->getBrands(true, 'name', 'DESC');
        $count = count($brands);
        $this->assertIsArray($brands);
        $this->assertGreaterThan(0, $count);
    }

    public function testIsNoExistBrand()
    {
        $brand = self::$o->packagesdb->isBrandExist(999999);
        $this->assertFalse($brand);
    }

    public function testProductsOui()
    {
        $oui = self::$o->packagesdb->getOUIAll();
        $count = count($oui);
        $this->assertIsArray($oui);
        $this->assertGreaterThan(0, $count);
    }
}