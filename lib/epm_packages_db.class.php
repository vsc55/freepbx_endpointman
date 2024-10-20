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
     * 
     * @example
     * $name = 'brand_name';
     * $brand = $this->getBrandByName($name);
     * if ($brand->isExistID())
     * {
     *  echo "Brand exists";
     * }
     * else
     * {
     *  echo "Brand not exists";
     * }
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
     * 
     * @example
     * $id = 'brand_id';
     * $brand = $this->getBrandByID($id);
     * if ($brand->isExistID())
     * {
     *   echo "Brand exists";
     * }
     * else
     * {
     *   echo "Brand not exists";
     * }
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
     * 
     * @example
     * $brands = $this->getBrands();
     * foreach ($brands as $brand)
     * {
     *   echo $brand->getName();
     * }
     * 
     * @example
     * $brands = $this->getBrands(true, 'name', 'DESC');
     * foreach ($brands as $brand)
     * {
     *  echo $brand->getName();
     * }
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


    /**
     * Get a product by ID
     * 
     * @param string $id Product ID
     * @return Provisioner\ProvisionerFamilyDB Return a ProvisionerFamilyDB object with the product data
     * 
     * @example
     * $id = 'product_id';
     * $product = $this->getProductByID($id);
     * if ($product->isExistID())
     * {
     *   echo "Product exists";
     * }
     * else
     * {
     *  echo "Product not exists";
     * }
     */
    public function getProductByID($id)
    {
        $product = new Provisioner\ProvisionerFamilyDB($this->epm);
        $product->findBy('id', $id);
        return $product;
    }

    /**
     * Get a Model by ID
     * 
     * @param string $id Model ID
     * @return Provisioner\ProvisionerModelDB Return a ProvisionerModelDB object with the model data
     * 
     * @example
     * $id = 'model_id';
     * $model = $this->getModelByID($id);
     * if ($model->isExistID())
     * {
     *    echo "Model exists";
     * }
     * else
     * {
     *   echo "Model not exists";
     * }
     */
    public function getModelByID($id)
    {
        $model = new Provisioner\ProvisionerModelDB($this->epm);
        $model->findBy('id', $id);
        return $model;
    }



    /**
     * Get all models with is enabled
     * 
     * @param bool $showAll True to show all models or false to show only the models is hidden is false
     * @param bool $indexModels True to index the models by ID or false to return the models as an array
     * @return array Return an array with models or an array indexed by ID depending on the $indexModels parameter
     * 
     * @example
     * $models = $this->getModelsEnabled();
     * foreach ($models as $model)
     * {
     *   echo $model->getName();
     * }
     * 
     * @example
     * $models = $this->getModelsEnabled(true, false);
     * foreach ($models as $model)
     * {
     *  echo $model->getName();
     * }
     */
    public function getModelsEnabled(bool $showAll = false, bool $indexModels = true)
    {
        $models = [];
        foreach ($this->getBrands(true, 'name') as $brand)
        {
            foreach($brand->getProducts($showAll) as $product)
            {
                $models_product = $product->getModels($showAll, true);
                if ($models_product)
                {
                    // $models = array_merge($models, $models_product);
                    foreach ($models_product as $model)
                    {
                        $id = $model->getID();
                        $models[$id] = $model;
                    }
                }   
            }
        }
        return $indexModels ? $models : array_values($models);
    }

    /**
     * Get all products with models enabled
     * 
     * @param bool $showAll True to show all products or false to show only the products and models is hidden is false
     * @param bool $indexProducts True to index the products by ID or false to return the products as an array
     * @return array Return an array with products or an array indexed by ID depending on the $indexProducts parameter
     * 
     * @example
     * $products = $this->getProductsByModelsEnabled();
     * foreach ($products as $product)
     * {
     *  echo $product->getName();
     * }
     * 
     * @example
     * $products = $this->getProductsByModelsEnabled(true, false);
     * foreach ($products as $product)
     * {
     * echo $product->getName();
     * }
     */
    public function getProductsByModelsEnabled(bool $showAll = false, bool $indexProducts = true)
    {
        $products = [];
        foreach ($this->getBrands(true, 'name') as $brand)
        {
            foreach($brand->getProducts($showAll) as $product)
            {
                $modelCount = $product->countModels($showAll, true);
                if ($modelCount > 0)
                {
                    $products[$product->getID()] = $product;
                }
            }
        }
        return $indexProducts ? $products : array_values($products);
    }

    /**
     * Get All OUIs
     * 
     * @param bool $indexOUI True to index the OUIs by OUI or false to return the OUIs as an array
     * @return array Return an array with OUIs or an array indexed by OUI depending on the $indexOUI parameter
     * 
     * @example
     * $ouis = $this->getOUIAll();
     * foreach ($ouis as $oui)
     * {
     *   echo $oui['oui'];
     * }
     * 
     * @example
     * $ouis = $this->getOUIAll(false);
     * foreach ($ouis as $oui)
     * {
     *   echo $oui['oui'];
     * }
     */
    public function getOUIAll(bool $indexOUI = true)
    {
        $ouis = [];
        foreach ($this->getBrands(true, 'name') as $brand)
        {
            $brand_id 	= $brand->getId();
            $brand_name = $brand->getName();
            foreach($brand->getOUI_All() as $oui)
            {
                $ouis[$oui['oui']] = array(
                    'id' 	   => $oui['id'],
                    'oui' 	   => $oui['oui'],
                    'brand_id' => $brand_id,
                    'brand'    => $brand_name, 
                    'custom'   => $oui['custom']
                );
            }
        }
        return $indexOUI ? $ouis : array_values($ouis);
    }

}