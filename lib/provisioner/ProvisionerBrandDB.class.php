<?php
namespace FreePBX\modules\Endpointman\Provisioner;

use FreePBX\modules\Endpointman;

require_once(__DIR__.'/ProvisionerBaseDB.class.php');
require_once(__DIR__.'/ProvisionerFamilyDB.class.php');

class ProvisionerBrandDB extends ProvisionerBaseDB
{
    protected $id      = null;
    protected $destory = false;

    public function __construct($epm, array $new = [], bool $debug = true)
    {
        parent::__construct($debug);
        if (! empty($epm))
        {
            $this->parent = $epm;
        }
        else
        {
            throw new \Exception(_("Endpoint Manager object is required"));
        }

        if (! empty($new))
        {
            return $this->create($new);
        }
    }


    /**
     * Create a new brand.
     * 
     * @param array $new The data to create the brand. The array must have the following keys: id, name, directory. Is not allow id = '0'.
     * @param bool $return_new_id If true, it will return the new ID of the brand created otherwise it will return true or false.
     * @param bool $noException If true, it will not throw an exception if the parent object is not set.
     * @return bool|int True if the brand is created, false otherwise. If $return_new_id is true, it will return the new ID of the brand.
     * @example
     */
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
        $missing_keys = array_diff_key(array_flip(['id', 'name', 'directory']), $new);
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
            $msg = sprintf(_("Brand ID '%s' already exists!"), $this->id);
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }

        // Set the default values if they are not set
        $new['cfg_ver']     = $new['cfg_ver']   ?? '';
        $new['installed']   = $new['installed'] ?? false;
        $new['local']       = $new['local']     ?? false;
        $new['hidden']      = $new['hidden']    ?? false;

        // Convert the boolean values to integer
        $new['installed']   = $new['installed'] ? 1 : 0;
        $new['local']       = $new['local']     ? 1 : 0;
        $new['hidden']      = $new['hidden']    ? 1 : 0;

        $return_insert = $this->insertQuery("endpointman_brand_list", $new, ['id'], true);
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
        
        $this->id = $new['id'];
        return $return_new_id ? $new['id'] : true;
    }



    /**
     * Remove the brand from the database and destroy the object.
     */
    public function delete()
    {
        if (! $this->isExistID())
        {
            return false;
        }

        foreach ($this->getProducts() as &$product)
        {
            $product->delete();
        }
        $countProducts = $this->countProducts();
        if ($countProducts > 0)
        {
            $msg = sprintf(_("The brand has %s products. You must delete the products before deleting the brand."), $countProducts);
            throw new \Exception($msg);
        }

        foreach ($this->getOUI_All(true) as $oui)
        {
            $this->deleteQuery("endpointman_oui_list", $oui['id']);
        }
        $countOUI = $this->countOUI();
        if ($countOUI > 0)
        {
            $msg = sprintf(_("The brand has %s OUIs. You must delete the OUIs before deleting the brand."), $countOUI);
            throw new \Exception($msg);
        }

        if (! $this->deleteQuery("endpointman_brand_list", $this->id))
        {
            $msg = _("Delete Query Failed!");
            throw new \Exception($msg);
        }
        
        $this->id      = null;
        $this->destory = true;
        
        return true;
    }

    /**
     * Checks if the object is destroyed.
     */
    public function isDestory()
    {
        return $this->destory;
    }


    /**
     * Find the brand by the given value.
     * 
     * @param string $find The value to find, is the column name in the database.
     * @param string $value The value to search for, is the value in the column.
     * @return bool True if the brand is found, false otherwise.
     * @example
     * $brand = new ProvisionerBrandDB($epm);
     * $brand->findBy('id', 1);
     * $brand->findBy('name', 'Yealink');
     * $brand->findBy('directory', 'yealink');
     */
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
        if (! in_array($find, ['id', 'name', 'directory', 'cfg_ver', 'installed', 'local', 'hidden']))
        {
            return false;
        }
        $where = [
            $find => [
                'operator' => "=", 
                'value' => $value
            ],
        ];

        $id = $this->querySelect("endpointman_brand_list", "id", $where, 1, true);
        if (empty($id))
        {
            return false;
        }
        $this->id = $id;
        return true;
    }

    public function getID()
    {
        if (empty($this->id) || $this->destory)
        {
            return null;
        }
        return $this->id;
    }

    /**
     * Check if the ID exists in the database.
     * 
     * @param int $id The ID to check if exists in the database. If empty, it will check the ID of the object. Is not allow id = '0'.
     * @return bool True if the ID exists, false otherwise.
     * 
     * @example
     * $brand = new ProvisionerBrandDB($epm);
     * $brand->findBy('id', 1);
     * $brand->isExistID();
     * Returns true if the ID 1 exists in the database.
     * 
     * @example
     * $brand = new ProvisionerBrandDB($epm);
     * $brand->isExistID(1);
     * Returns true if the ID 1 exists in the database.
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
        $count = $this->querySelect("endpointman_brand_list", "COUNT(*) as total", $id, 1, true);
        if ($count === 0)
        {
            return false;
        }
        return true;
    }

    /**
     * Get the name of the brand.
     */
    public function getName()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_brand_list", "name", $this->id, 1, true);
        
        if (empty($result))
        {
            return false;
        }
        return $result;
    }

    /**
     * Get the directory of the brand.
     */
    public function getDirectory()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_brand_list", "directory", $this->id, 1, true);

        if (empty($result))
        {
            return false;
        }
        return $result;
    }

    /**
     * Get the last modified date of the brand.
     * Is the version of the configuration file 'cfg_ver' in the database.
     */
    public function getLastModified()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_brand_list", "cfg_ver", $this->id, 1, true);

        if (empty($result))
        {
            return false;
        }
        return $result;
    }

    /**
     * Set the last modified date of the brand.
     * 
     * @param string $cfg_ver The version of the configuration file.
     * @return bool True if the brand is updated, false otherwise.
     */
    public function setLastModified(?string $cfg_ver = "")
    {
        if (! $this->isExistID())
        {
            return false;
        }
        return $this->updateQuery("endpointman_brand_list", ['cfg_ver' => $cfg_ver], $this->id);
    }

    /**
     * Get the installed status of the brand.
     * If the brand is installed, it will return true otherwise false.
     * In the database, the value is 1 if the brand is installed, otherwise 0.
     */
    public function getInstalled()
    {
        if (! $this->isExistID())
        {
            return null;
        }
        $result = $this->querySelect("endpointman_brand_list", "installed", $this->id, 1, true);
        
        if (is_numeric($result) && $result == 1)
        {
            return true;
        }
        return false;
    }

    /**
     * Set the installed status of the brand.
     * 
     * @param bool $installed If true, it will set the brand as installed otherwise uninstalled.
     * @return bool True if the brand is updated, false otherwise. In database, the value is 1 if the brand is installed, otherwise 0.
     */
    public function setInstalled(bool $installed)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $installed = $installed ? 1 : 0;
        return $this->updateQuery("endpointman_brand_list", ['installed' => $installed], $this->id);
    }

    /**
     * Get the local status of the brand.
     * If the brand is local, it will return true otherwise false.
     * In the database, the value is 1 if the brand is local, otherwise 0.
     */
    public function getLocal()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_brand_list", "local", $this->id, 1, true);
        if (is_numeric($result) && $result == 1)
        {
            return true;
        }
        return false;
    }

    /**
     * Set the local status of the brand.
     * 
     * @param bool $local If true, it will set the brand as local otherwise remote.
     * @return bool True if the brand is updated, false otherwise. In database, the value is 1 if the brand is local, otherwise 0.
     */
    public function setLocal(bool $local)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $local = $local ? 1 : 0;
        return $this->updateQuery("endpointman_brand_list", ['local' => $local], $this->id);
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
        $result = $this->querySelect("endpointman_brand_list", "hidden", $this->id, 1, true);
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
        return $this->updateQuery("endpointman_brand_list", ['hidden' => $hidden], $this->id);
    }


    /**
     * Get all the OUIs of the brand.
     * 
     * @param bool $custom If true, it will get all the OUIs of the brand otherwise only the OUIs that are not custom.
     * @return array An array with all the OUIs of the brand.
     * 
     * @example
     * $brand = new ProvisionerBrandDB($epm);
     * $brand->findBy('id', 1);
     * $brand->getOUI_All();
     * Returns all the OUIs of the brand with the ID 1.
     */
    public function getOUI_All(bool $custom = true)
    {
        if (! $this->isExistID())
        {
            return [];
        }
        $where = [
            'brand' => [
                'operator' => "=", 
                'value'    => $this->id
            ],
        ];

        // If custom is false, we will only get the OUIs that are not custom
        if (! $custom)
        {
            $where['custom'] = [
                'operator' => "=", 
                'value'    => 0
            ];
        }

        $result = $this->querySelect("endpointman_oui_list", "*", $where);
        if ($result === false)
        {
            return [];
        }
        return $result;
    }

    /**
     * Get the OUI by the ID.
     * 
     * @param int $oui_id The ID of the OUI to get.
     * @return array The OUI information.
     * 
     * @example
     * $brand = new ProvisionerBrandDB($epm);
     * $brand->findBy('id', 1);
     * $brand->getOUI(1);
     * Returns the OUI information of the ID 1.
     */
    public function getOUI($oui_id)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_oui_list", "*", $oui_id);
        if ($result === false)
        {
            return false;
        }
        return $result;
    }
    
    /**
     * Get number of OUIs of the brand.
     * 
     * @return int The number of OUIs of the brand.
     * 
     * @example
     * $brand = new ProvisionerBrandDB($epm);
     * $brand->findBy('id', 1);
     * $brand->countOUI();
     * Returns the number of OUIs of the brand with the ID 1.
     */
    public function countOUI(bool $custom = true)
    {
        $oui = $this->getOUI_All($custom);
        if (empty($oui) || ! is_array($oui))
        {
            return 0;
        }
        return count($oui);
    }

    /**
     * Add a new OUI to the brand.
     * 
     * @param string $oui The OUI to add to the brand.
     * @param bool $custom If true, it will set the OUI as custom otherwise not custom.
     * @return bool True if the OUI is added, false otherwise.
     */
    public function setOUI(string $oui, bool $custom = false)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $new = [
            'brand'  => $this->id,
            'oui'    => $oui,
            'custom' => $custom ? 1 : 0,
        ];
        return $this->remplaceQuery("endpointman_oui_list", $new);
    }

    /**
     * Check if the OUI exists in the database.
     * 
     * @param string $oui The OUI to check if exists in the database.
     * @return bool True if the OUI exists, false otherwise.
     */
    public function isExistOUI(string $oui)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $where = [
            'brand' => [
                'operator' => "=", 
                'value'    => $this->getID()
            ],
            'oui' => [
                'operator' => "=", 
                'value'    => $oui
            ],
        ];
        $count = $this->querySelect("endpointman_oui_list", "COUNT(*) as total", $where, 1, true);
        if ($count === 0)
        {
            return false;
        }
        return true;
    }

    /**
     * Delete the OUI by the ID.
     * 
     * @param int $oui_id The ID of the OUI to delete.
     * @return bool True if the OUI is deleted, false otherwise.
     */
    public function deleteOUIByID(int $oui_id)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        return $this->deleteQuery("endpointman_oui_list", $oui_id);
    }


    /**
     * Get the list of products of the brand.
     * 
     * @param string $select The columns to select in the query. Default is '*'.
     * @param bool $showAll If true, it will show all the products otherwise only the products that are not hidden.
     * @return array An array with the list of products of the brand.
     */
    public function getProductsList(string $select = "*", bool $showAll = true)
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
            'brand' => [
                'operator' => "=", 
                'value'    => $this->id
            ],
        ];
        if (! $showAll)
        {
            $where['hidden'] = [
                'operator' => "=", 
                'value'    => 0
            ];
        }
        return $this->querySelect("endpointman_product_list", $select, $where);
    }

    /**
     * Count the number of products of the brand.
     * 
     * @param bool $showAll If true, it will show all the products otherwise only the products that are not hidden.
     * @return int The number of products of the brand.
     */
    public function countProducts(bool $showAll = true)
    {
        $products = $this->getProductsList("id", $showAll);
        if (empty($products) || ! is_array($products))
        {
            return 0;
        }
        return count($products);
    }

    /**
     * Get the products of the brand.
     * 
     * @param bool $showAll If true, it will show all the products otherwise only the products that are not hidden.
     * @return array An array with the products of the brand as ProvisionerFamilyDB objects.
     */
    public function getProducts(bool $showAll = true)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $products = [];
        foreach ($this->getProductsList("id", $showAll) as $value)
        {
            $id = $value['id'] ?? null;
            if (empty($id))
            {
                continue;
            }
            $new_product = new ProvisionerFamilyDB($this->parent, $this);
            $new_product->findBy('id', $id);
            $products[$id] = $new_product;
        }
        return $products;        
    }

    /**
     * Get the product by the ID.
     * 
     * @param int $product_id The ID of the product to get.
     * @return ProvisionerFamilyDB|bool The product as ProvisionerFamilyDB object if found, false otherwise.
     */
    public function getProduct(int $product_id)
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $product = new ProvisionerFamilyDB($this->parent, $this);
        $product->findBy('id', $product_id);
        if ($product->isExistID())
        {
            return false;
        }
        return $product;
    }






    /**
     * Export the brand information.
     * 
     * @return array An array with the brand information.
     */
    public function exportInfo(){
        $info = [
            'isPartent' => array(
                'get'  => $this->isParentSet(),
                'type' => gettype($this->parent),
            ),
            'system'    => gettype($this->system),
            'debug'     => $this->debug,
            'id'        => array (
                'get' => $this->getID(),
                'raw' => $this->id,
            ),
            'name'      => $this->getName(),
            'directory' => $this->getDirectory(),
            'cfg_ver'   => $this->getLastModified(),
            'installed' => $this->getInstalled(),
            'local'     => $this->getLocal(),
            'hidden'    => $this->getHidden(),
            'oui'       => $this->getOUI_All(true),
            'oui_count' => $this->countOUI(),
            'isDestory' => array (
                'get' => $this->isDestory(),
                'raw' => $this->destory,
            ),
        ];
        return $info;
    }

}