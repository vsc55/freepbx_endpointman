<?php
namespace FreePBX\modules\Endpointman\Provisioner;

require_once('ProvisionerBaseDB.class.php');
require_once('ProvisionerBrandDB.class.php');
// require_once('ProvisionerModelDB.class.php');

class ProvisionerFamilyDB extends ProvisionerBaseDB
{
    protected $id      = null;
    protected $brand   = null;
    protected $destory = false;

    public function __construct($epm, $brand = null, array $new = [], bool $debug = true)
    {
        parent::__construct($debug);
        if (! empty($epm))
        {
            $this->parent = $epm;
        }
        else
        {
            throw new \Exception(_("Parent object is required!"));
        }
        if (! empty($brand))
        {
            $this->brand = $brand;
        }

        if (! empty($new))
        {
            return $this->create($new);
        }
    }

    /**
     * Checks if the object is destroyed.
     */
    public function isDestory()
    {
        return $this->destory;
    }

    /**
     * Checks if the ID exists in the database.
     * 
     * @param int|null $id The ID to check.
     * @param bool $noException True to not throw an exception, false otherwise.
     * @return bool True if the ID exists, false otherwise.
     * @throws \Exception If the ID is required or the object is destroyed.
     * @throws \Exception If the ID does not exist.
     * @throws \Exception If the query fails.
     */
    public function isExistID(?int $id = null, bool $noException = true)
    {
        if ($this->destory)
        {
            if ($noException) { return false; }
            throw new \Exception(_("The object is destroyed!"));
        }
        if (empty($id))
        {
            $id = $this->id;
        }
        if (empty($id))
        {
            if ($noException) { return false; }
            throw new \Exception(_("The ID is required!"));
        }
        $count = $this->querySelect("endpointman_product_list", "COUNT(*) as total", $id, 1, true);
        if ($count === 0)
        {
            return false;
        }
        return true;
    }

    public function findBy($find, $value)
    {
        if (empty($find) || empty($value))
        {
            return false;
        }
        if (! $this->isParentSet())
        {
            return false;
        }
        if (! in_array($find, ['id', 'brand', 'long_name', 'short_name', 'cfg_dir', 'cfg_ver', 'firmware_vers', 'firmware_files', 'config_files', 'hidden']))
        {
            return false;
        }
        $where = [
            $find => [
                'operator' => "=", 
                'value' => $value
            ],
        ];

        $row = $this->querySelect("endpointman_product_list", "id, brand", $where, 1);

        if (empty($row))
        {
            return false;
        }
        $id    = $row['id'];
        $brand = $row['brand'];
        if (empty($id))
        {
            return false;
        }
        $this->id = $id;

        if (! $this->isBrandSet())
        {
            $this->setBrand(is_numeric($brand) ? $brand: null);
        }

        return true;
    }

    public function create(array $new, bool $return_new_id = false, bool $noException = true)
    {
        if (! $this->isParentSet())
        {
            $msg = _("Parent object is not set!");
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        elseif (empty($new))
        {
            $msg = _("No data to create the brand!");
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        elseif (! is_array($new))
        {
            $msg = _("The data to create the brand must be an array!");
            if ( $noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        
        // Check if the required keys are set
        $missing_keys = array_diff_key(array_flip(['id', 'brand', 'long_name', 'short_name', 'cfg_dir', 'cfg_ver']), $new);
        if (! empty($missing_keys))
        {
            $msg = sprintf(_("Missing keys: %s"), implode(', ', array_keys($missing_keys)));
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        unset($missing_keys);

        // Check if the ID is exists in the database
        if ($this->isExistID($new['id']))
        {
            $msg = sprintf(_("Product ID '%s' already exists!"), $this->id);
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }

        // Set the default values if they are not set
        $new['cfg_ver']     = $new['cfg_ver']   ?? '';
        $new['hidden']      = $new['hidden']    ?? false;

        // Convert the boolean values to integer
        $new['hidden']      = $new['hidden']    ? 1 : 0;

        // Format the configuration files
        $new['config_files'] = is_array($new['config_files']) ? implode(",", $new['config_files']) : $new['config_files'];

        $return_insert = $this->insertQuery("endpointman_product_list", $new, ['id'], true);
        if ($return_insert === false)
        {
            $msg = _("Insert Query Failed!");
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        
        if (! $this->findBy('id', $new['id']))
        {
            $msg = _("Product not created!");
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        return $return_new_id ? $new['id'] : true;
    }

    /**
     * Deletes the family from the database.
     */
    public function delete()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        //TODO: Delete the models before deleting the family
        $result = $this->deleteQuery("endpointman_product_list", $this->id);
        if ($result === false)
        {
            return false;
        }
        $this->id      = null;
        $this->destory = true;
    }



    
    public function getBrandId(bool $viaThisBrond = false)
    {
        if ($viaThisBrond)
        {
            if (! $this->isBrandSet())
            {
                return false;
            }
            return $this->brand->getID();
        }
        else
        {
            if (! $this->isExistID())
            {
                return false;
            }
            $result = $this->querySelect("endpointman_product_list", "brand", $this->id, 1, true);
            if (empty($result))
            {
                return false;
            }
            return $result;
        }
    }

    public function getBrand()
    {
        return $this->brand;
    }
    
    public function setBrand($new_brand = null)
    {
        if(is_numeric($new_brand))
        {
            $brand = new ProvisionerBrandDB($this->parent);
            $brand->findBy('id', $new_brand);
        }
        else if (is_string($new_brand))
        {
            $brand = new ProvisionerBrandDB($this->parent);
            $brand->findBy('directory', $new_brand);
        }
        else if ($new_brand instanceof ProvisionerBrandDB || is_null($new_brand))
        {
            $brand = $new_brand;
        }
        else
        {
            return false;
        }

        $this->brand = $brand;
        return $this->brand;
    }

    public function isBrandSet()
    {
        if (empty($this->brand))
        {
            return false;
        }
        else if (! $this->brand instanceof ProvisionerBrandDB)
        {
            return false;
        }
        else if ($this->brand->isDestory())
        {
            return false;
        }
        return true;
    }

















    
    /**
     * Retrieves the id of the family (product) from the database.
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Get the hidden status of the brand.
     * If the brand is hidden, it will return true otherwise false.
     * In the database, the value is 1 if the brand is hidden, otherwise 0.
     */
    public function getHidden()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_product_list", "hidden", $this->id, 1, true);
        if (is_numeric($result) && $result == 1)
        {
            return true;
        }
    }

    /**
     * Set the hidden status of the brand.
     * 
     * @param bool $hidden If true, it will set the brand as hidden otherwise visible.
     * @return bool True if the brand is updated, false otherwise. In database, the value is 1 if the brand is hidden, otherwise 0.
     */
    public function setHidden(bool $hidden)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $hidden = $hidden ? 1 : 0;
        return $this->updateQuery("endpointman_product_list", ['hidden' => $hidden], $this->id);
    }

    /**
     * Get the name the family (product).
     */
    public function getName()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_product_list", "long_name", $this->id, 1, true);
        if (empty($result))
        {
            return false;
        }
        return $result;
    }

    /**
     * Set the name of the family (product).
     * 
     * @param string $name The name to set.
     * @return bool True if the name is updated, false otherwise.
     */
    public function setName(string $name)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        return $this->updateQuery("endpointman_product_list", ['long_name' => $name], $this->id);
    }

    /**
     * Get the short name of the family (product).
     */
    public function getShortName()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_product_list", "short_name", $this->id, 1, true);
        if (empty($result))
        {
            return false;
        }
        return $result;
    }

    /**
     * Set the short name of the family (product).
     * 
     * @param string $name The name to set.
     * @return bool True if the name is updated, false otherwise.
     */
    public function setShortName(string $name)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        return $this->updateQuery("endpointman_product_list", ['short_name' => $name], $this->id);
    }

    /**
     * Get the description of the family (product).
     */
    public function getDirectory()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_product_list", "cfg_dir", $this->id, 1, true);
        if (empty($result))
        {
            return false;
        }
        return $result;
    }

    /**
     * Get the last modified date of the family (product).
     */
    public function getLastModified()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_product_list", "cfg_ver", $this->id, 1, true);
        if (empty($result))
        {
            return false;
        }
        return $result;
    }

    /**
     * Set the last modified date of the family (product).
     * 
     * @param string $date The date to set.
     * @return bool True if the date is updated, false otherwise.
     */
    public function setLastModified(string $date)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        return $this->updateQuery("endpointman_product_list", ['cfg_ver' => $date], $this->id);
    }

    /**
     * Get the firmware version of the family (product).
     */
    public function getFirmwareVer()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_product_list", "firmware_vers", $this->id, 1, true);
        if (empty($result))
        {
            return false;
        }
        return $result;
    }

    /**
     * Set the firmware version of the family (product).
     * 
     * @param string $version The version to set.
     * @return bool True if the version is updated, false otherwise.
     */
    public function setFirmwareVer(string $version)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        return $this->updateQuery("endpointman_product_list", ['firmware_vers' => $version], $this->id);
    }

    /**
     * Get the firmware package of the family (product).
     */
    public function getFirmwarePkg()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_product_list", "firmware_files", $this->id, 1, true);
        if (empty($result))
        {
            return false;
        }
        return $result;
    }
    
    /**
     * Set the firmware package of the family (product).
     * 
     * @param string $pkg The package to set.
     * @return bool True if the package is updated, false otherwise.
     */
    public function setFirmwarePkg(string $pkg)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        return $this->updateQuery("endpointman_product_list", ['firmware_files' => $pkg], $this->id);
    }

    /**
     * Get the configuration files of the family (product).
     */
    public function getConfigurationFiles()
    {
        if (! $this->isExistID())
        {
            return [];
        }
        $result = $this->querySelect("endpointman_product_list", "config_files", $this->id, 1, true);
        $retuls = explode(",", $result ?? "");
        if (empty($result))
        {
            return [];
        }
        return $result;
    }

    public function setConfigurationFiles(array $files = [])
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $files = implode(",", $files);
        return $this->updateQuery("endpointman_product_list", ['config_files' => $files], $this->id);
    }

    
    
    
    public function getModulesList(string $select = "*")
    {
        if (! $this->isExistID())
        {
            return [];
        }
        else if (empty($select) || ! is_string($select))
        {
            $select = "*";
        }
        $where = [
            'product_id' => [
                'operator' => "=", 
                'value' => $this->id
            ],
        ];
        $rows = $this->querySelect("endpointman_model_list", $select, $where);
        return $rows;
    }

    public function getModels()
    {
        if (! $this->isExistID())
        {
            return [];
        }
        $model_list = [];
        foreach ($this->getModulesList("id") as $model)
        {
            $new_model = new ProvisionerModelDB($this->parent);
            $new_model->findBy('id', $model['id']);
            $model_list[$model['id']] = $new_model;
        }
        return $model_list;
    }

    public function countModels()
    {
        $count = $this->getModulesList('id');
        if (empty($count))
        {
            return 0;
        }        
        return count($count);
    }

    // public function isModelExist(string $modelName)
    // {
    //     if (! empty($modelName))
    //     {
    //         foreach ($this->getModelList() as $model)
    //         {
    //             if ($model->getModel() == $modelName)
    //             {
    //                 return true;
    //             }
    //         }
    //     }
    //     return false;
    // }

    public function getModel(int $modelId)
    {
        $new_model = new ProvisionerModelDB($this->parent);
        $new_model->findBy('id', $modelId);
        return $new_model;
    }

    // public function getModelTemplateList(string $modelName)
    // {
    //     if (! empty($modelName))
    //     {
    //         foreach ($this->getModelList() as $model)
    //         {
    //             if ($model->getModel() == $modelName)
    //             {
    //                 return $model->getTemplateList();
    //             }
    //         }
    //     }
    //     return [];
    // }
    
    
    
    




    
    /**
     * Export the family information.
     * 
     * @return array An array with the family information.
     */
    public function exportInfo(){
        $info = [
            'isPartent'     => array(
                'get'  => $this->isParentSet(),
                'type' => gettype($this->parent),
            ),
            'system'        => gettype($this->system),
            'debug'         => $this->debug,
            'id'            => array (
                'get' => $this->getID(),
                'raw' => $this->id,
            ),
            'name'          => $this->getName(),
            'directory'     => $this->getDirectory(),
            'cfg_ver'       => $this->getLastModified(),
            'firmware'      => $this->getFirmwareVer(),
            'firmware_pkg'  => $this->getFirmwarePkg(),
            'cfg_files'     => $this->getConfigurationFiles(),

            'models'        => $this->getModels(),
            'model_count'   => $this->countModels(),
            
            
            'isDestory' => array (
                'get' => $this->isDestory(),
                'raw' => $this->destory,
            ),
        ];
        return $info;
    }


}