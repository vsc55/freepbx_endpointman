<?php
namespace FreePBX\modules\Endpointman;

require_once('provisioner/ProvisionerBrandDB.class.php');
require_once('provisioner/ProvisionerFamilyDB.class.php');
require_once('provisioner/ProvisionerModelDB.class.php');

class PackagesDB
{
    public $epm       = null;
    public $system    = null;
    public $freepbx   = null;
    public $db        = null;
    public $config    = null;
    public $configepm = null;
    

    public function __construct($epm = null)
    {
        if (empty($epm))
        {
            throw new \Exception(_("Endpoint Manager object is required"));
        }

        $this->epm       = $epm;            // Endpoint Manager object
        $this->freepbx   = $epm->freepbx;   // FreePBX object
		$this->db        = $epm->db;        // Database object configurate in the Endpoint Manager object
    }

    /**
     * Get a brand by name
     * 
     * @param string $name Brand name
     * @return Provisioner\ProvisionerBrandDB Return a ProvisionerBrandDB object with the brand data
     */
    public function getBrandByDirectory($name)
    {
        $brand = new Provisioner\ProvisionerBrandDB($this->epm);
        $brand->findBy('directory', $name);
        return $brand;
    }

    /**
     * Get a brand by ID
     * 
     * @param string $id Brand ID
     * @return Provisioner\ProvisionerBrandDB Return a ProvisionerBrandDB object with the brand data
     */
    public function getBrandByID($id)
    {
        $brand = new Provisioner\ProvisionerBrandDB($this->epm);
        $brand->findBy('id', $id);
        return $brand;
    }

    /**
     * Get all brands
     * 
     * @return array Return an array with all brands
     */
    public function getBrands(bool $showAll = true, string $orderby = "id", string $order = "ASC")
    {
        $brands = [];
        $sql  = sprintf("SELECT id FROM %s", "endpointman_brand_list");
        if ($showAll == false)
        {
            $sql .= " WHERE hidden = 0";
        }
        if (!empty($orderby))
        {
            $orderby = in_array($orderby, ['id', 'name', 'directory']) ? $orderby : "id";
            $order   = strtoupper($order) == "DESC" ? "DESC" : "ASC";

            $sql .= sprintf(" ORDER BY %s %s", $orderby, $order);
        }

        $stmt = $this->epm->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row)
        {
            if (empty($row['id']))
            {
                continue;
            }
            $brand = new Provisioner\ProvisionerBrandDB($this->epm);
            $brand->findBy('id', $row['id']);
            $brands[] = $brand;
        }
        return $brands;
    }

    /**
     * Check if a brand exists
     * 
     * @param string $id Brand ID
     * @return bool Return true if the brand exists or false if not
     * 
     * @example
     * $id = 'brand_id';
     * if ($this->isBrandExist($id))
     * {
     *     echo "Brand exists";
     * }
     * else
     * {
     *    echo "Brand not exists";
     * }
    */
    public function isBrandExist($id)
    {
        $brand = new Provisioner\ProvisionerBrandDB($this->epm);
        $brand->findBy('id', $id);
        return $brand->isExistID();
    }


    /**
     * Create a new brand
     * 
     * @param array $data Data to create a new brand (id, name, directory, etc...)
     * @param bool $return_id True to return the new ID of the brand or false to return true if the brand was created successfully or false if not
     * @param bool $noException True to return false if an exception is thrown or false to throw the exception
     * @return bool|int Return the new ID of the brand or true if the brand was created successfully or false if not
     * @throws Exception If the brand ID is required
     * @throws Exception If the brand ID already exists
     * @throws Exception If an exception is thrown and $noException is false
     * 
     * @example
     * $data = [
     *    'id'        => 'brand_id',
     *    'name'      => 'Brand Name',
     *    'directory' => 'brand_directory',
     * ];
     * $result = $this->createBrand($data);
     * 
     * if ($result)
     * {
     *      echo "Brand created successfully";
     * }
     * else
     * {
     *      echo "Brand not created";
     * }
     * 
     * @example
     * $data = [
     *   'id'        => 'brand_id',
     *   'name'      => 'Brand Name',
     *   'directory' => 'brand_directory',
     * ];
     * try
     * {
     *      $result = $this->createBrand($data, true, false);
     *      echo "Brand created successfully with ID: $result";
     * }
     * catch (Exception $e)
     * {
     *      echo $e->getMessage();
     * }
     * 
     * @example
     * $data = [
     *      'id'        => 'brand_id',
     *      'name'      => 'Brand Name',
     *      'directory' => 'brand_directory',
     * ];
     * $result = $this->createBrand($data, true, true);
     * 
     * if ($result)
     * {
     *      echo "Brand created successfully with ID: $result";
     * }
     * else
     * {
     *      echo "Brand not created";
     * }
     */
    public function createBrand(array $data = [], bool $return_id = false, bool $noException = true)
    {
        $id = $data['id'] ?? null;
        if (empty($id))
        {
            if ($noException)
            {
                return false;
            }
            throw new \Exception(_("Brand ID is required"));
        }

        $brand = new Provisioner\ProvisionerBrandDB($this->epm);
        if ($brand->isExistID($id))
        {
            if ($noException)
            {
                return false;
            }
            dbug($data);
            throw new \Exception(sprintf(_("Brand ID '%s' already exists"), $id));
        }

        try
        {
            $new_id = $brand->create($data, $return_id, $noException);  
        }
        catch (\Exception $e)
        {
            if ($noException)
            {
                return false;
            }
            throw $e;
        }

        if ($new_id)
        {
            return $return_id ? $new_id : true;
        }
        return false;
    }



    public function getProductByID($id)
    {
        $product = new Provisioner\ProvisionerFamilyDB($this->epm);
        $product->findBy('id', $id);
        return $product;
    }

    public function getModelByID($id)
    {
        $model = new Provisioner\ProvisionerModelDB($this->epm);
        $model->findBy('id', $id);
        return $model;
    }




    public function getModelsEnabled()
    {
        $models = [];
        
        return $models;
        
    }

}