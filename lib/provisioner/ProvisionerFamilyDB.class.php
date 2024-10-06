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
        $this->destory = true;
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

        $this->destory = false;
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
        foreach ($this->getModels() as &$model)
        {
            $model->delete();
        }
        $countModules = $this->countModels();
        if ($countModules > 0)
        {
            throw new \Exception(sprintf(_("There are %s models that are not deleted!"), $countModules));
        }
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
    public function setFirmwareVer(?string $version)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $version = $version ?? "";
        return $this->updateQuery("endpointman_product_list", ['firmware_vers' => $version], $this->id);
    }

    /**
     * Check if the firmware version is set.
     * 
     * @return bool True if the firmware version is set, false otherwise.
     */
    public function isSetFirmwareVer()
    {
        $fw_ver = $this->getFirmwareVer();
        if (empty($fw_ver))
        {
            return false;
        }
        return true;
    }

    /**
     * Get the firmware package of the family (product).
     */
    public function getFirmwareFiles()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_product_list", "firmware_files", $this->id, 1, true);
        $result = explode(",", $result ?? "");
        $result = array_filter($result);
        // $result = array_filter($result, function($value) { return $value !== ''; });
        return $result;
    }
    
    /**
     * Set the firmware package of the family (product).
     * 
     * @param string $pkg The package to set.
     * @return bool True if the package is updated, false otherwise.
     */
    public function setFirmwareFiles(?array $files = [])
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $files = implode(",", $files ?? []);
        return $this->updateQuery("endpointman_product_list", ['firmware_files' => $files], $this->id);
    }

    public function countFirmwareFiles()
    {
        $count = $this->getFirmwareFiles();
        if (empty($count) || ! is_array($count))
        {
            return 0;
        }        
        return count($count);
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
        $result = explode(",", $result ?? "");
        $result = array_filter($result);
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

    public function countConfigurationFiles()
    {
        $count = $this->getConfigurationFiles();
        if (empty($count) || ! is_array($count))
        {
            return 0;
        }        
        return count($count);
    }


    public function getConfigurationsCustom()
    {
        if (! $this->isExistID())
        {
            return [];
        }
        $where = [
            'product_id' => [
                'operator' => "=", 
                'value' => $this->getId()
            ],
        ];
        $custom = $this->querySelect("endpointman_custom_configs", "*", $where);
        return $custom;
    }

    public function getConfigurationCustom(int $configId)
    {
        if (! $this->isExistID())
        {
            return [];
        }
        $where = [
            'id' => [
                'operator' => "=", 
                'value' => $configId
            ],
            'product_id' => [
                'operator' => "=", 
                'value' => $this->getId()
            ],
        ];
        $custom = $this->querySelect("endpointman_custom_configs", "*", $where, 1);
        return $custom;
    }

    public function setConfigurationCustom(string $original_name, string $name, string $data, bool $overwrite = false, bool $noException = true)
    {
        if (! $this->isExistID())
        {
            $msg = _("The family (product) does not exist!");
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }

        // Check if the required fields are set
        if (empty($original_name) || empty($name) || empty($data))
        {
            $msg = _("The original name, name, and data are required!");
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }

        // Sanitize the input
        // $original_name = addslashes($original_name);
        // $name          = addslashes($name);
        // $data          = addslashes($data);

        $where_exist = [
            'product_id' => [
                'operator' => "=", 
                'value' => $this->getId()
            ],
            'original_name' => [
                'operator' => "=", 
                'value' => $original_name
            ],
            'name' => [
                'operator' => "=", 
                'value' => $name
            ],
        ];

        if (empty($this->querySelect("endpointman_custom_configs", "id", $where_exist, 1, true)))
        {
            $new = [
                'product_id'    => $this->getId(),
                'original_name' => $original_name,
                'name'          => $name,
                'data'          => $data,
            ];
            return $this->insertQuery("endpointman_custom_configs", $new, ['id']);
        }
        elseif ($overwrite)
        {
            $where_update = [
                'product_id' => [
                    'operator' => "=", 
                    'value' => $this->getId()
                ],
                'original_name' => [
                    'operator' => "=", 
                    'value' => $original_name
                ],
                'name' => [
                    'operator' => "=", 
                    'value' => $name
                ],
            ];
            $new = [
                'data' => $data,
            ];
            return $this->updateQuery("endpointman_custom_configs", $new, $where_update);
        }
        else
        {
            $msg = _("The configuration already exists!");
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
    }

    public function countConfigurationsCustom()
    {
        $count = $this->getConfigurationsCustom();
        if (empty($count))
        {
            return 0;
        }        
        return count($count);
    }

    public function delConfigurationCustom(int $configId)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $where = [
            'id' => [
                'operator' => "=", 
                'value' => $configId
            ],
            'product_id' => [
                'operator' => "=", 
                'value' => $this->getId()
            ],
        ];
        return $this->deleteQuery("endpointman_custom_configs", $where);
    }





    
    
    /**
     * Get the list of models in the family (product).
     * 
     * @param string $select The columns to select, default is "*".
     * @param bool $showAll True to show all models, false otherwise. Default is true.
     * @param bool $onlyEnabled True to show only enabled models, false otherwise. Default is false.
     * @return array The list of models.
     */
    public function getModulesList(string $select = "*", bool $showAll = true, bool $onlyEnabled = false)
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
        if ($onlyEnabled)
        {
            $where['enabled'] = [
                'operator' => "=", 
                'value' => 1
            ];
        }
        if (! $showAll)
        {
            $where['hidden'] = [
                'operator' => "=", 
                'value' => 0
            ];
        }
        $rows = $this->querySelect("endpointman_model_list", $select, $where);
        return $rows;
    }

    /**
     * Get the list of models in the family (product).
     * 
     * @param bool $showAll True to show all models, false otherwise. Default is true.
     * @param bool $onlyEnabled True to show only enabled models, false otherwise. Default is false.
     * @return array The list of models, each model is an object of ProvisionerModelDB.
     */
    public function getModels(bool $showAll = true, bool $onlyEnabled = false)
    {
        if (! $this->isExistID())
        {
            return [];
        }
        $model_list = [];
        foreach ($this->getModulesList("id", $showAll, $onlyEnabled) as $model)
        {
            $new_model = new ProvisionerModelDB($this->parent);
            $new_model->findBy('id', $model['id']);
            $model_list[$model['id']] = $new_model;
        }
        return $model_list;
    }

    /**
     * Count the number of models in the family (product).
     * 
     * @param bool $showAll True to show all models, false otherwise.
     * @param bool $onlyEnabled True to show only enabled models, false otherwise.
     * @return int The number of models.
     */
    public function countModels(bool $showAll = true, bool $onlyEnabled = false)
    {
        $count = $this->getModulesList('id', $showAll, $onlyEnabled);
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


    /**
     * Get the model object.
     * 
     * @param int $modelId The ID of the model.
     * @return ProvisionerModelDB The model object.
     */
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
            'firmware_files'=> $this->getFirmwareFiles(),
            'firmware_count'=> $this->countFirmwareFiles(),
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