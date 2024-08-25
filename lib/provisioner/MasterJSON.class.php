<?php
namespace FreePBX\modules\Endpointman\Provisioner;

require_once(__DIR__.'/../epm_system.class.php');
require_once('ProvisionerBrand.class.php');

class MasterJSON
{
    private $lastModified = null;
    private $package      = null;
    private $version      = '';
    private $brands       = [];

    private $debug        = false;
    private $system       = null;

    /**
     * Creates a new MasterJSON object.
     *
     * @param array|string $jsonData The JSON data to import.
     * @param bool $debug Whether to enable debugging.
     */
    public function __construct($jsonData = null, $debug = true)
    {
        $this->debug  = $debug;
        $this->system = new \FreePBX\modules\Endpointman\epm_system();
        if (! empty($jsonData))
        {
            $this->importJSON($jsonData);
        }
    }

    /**
     * Imports the JSON data into the master JSON.
     *
     * @param array|string $jsonData The JSON data to import.
     * @param bool $noException Whether to throw an exception if the JSON data is invalid.
     * @throws \Exception If the JSON data is invalid.
     */
    public function importJSON($jsonData, $noException = false)
    {
        if (empty($jsonData))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Empty JSON data [%s]!"), __CLASS__));
        }
        elseif (is_string($jsonData))
        {
			try
			{
                $jsonData = $this->system->file2json($jsonData);
			}
			catch (\Exception $e)
			{
                if ($noException) { return false; }
                throw new \Exception(sprintf(_("%s [%s]!"), $e->getMessage(), __CLASS__));
			}
        }
        if (empty($jsonData['data']))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Invalid JSON data [%s]!"), __CLASS__));
        }

        $this->lastModified = $jsonData['data']['last_modified'] ?? null;
        $this->package      = $jsonData['data']['package']       ?? null;
        $this->version      = $jsonData['data']['version']       ?? '';

        $this->brands = [];
        foreach ($jsonData['data']['brands'] ?? array() as $brandData)
        {
            $name = $brandData['name']      ?? '';
            $dir  = $brandData['directory'] ?? '';
            if (empty($name) || empty($dir))
            {
                if ($this->debug)
                {
                    dbug(sprintf(_("Invalid brand data, name '%s', directory '%s'"), $name, $dir));
                }
                continue;
            }
            $this->brands[] = new ProvisionerBrand($name, $dir);
        }
        return true;
    }

    /**
     * Retrieves the last modified date.
     *
     * @return string The last modified date.
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Retrieves the package name.
     *
     * @return string The package name.
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Retrieves the version.
     *
     * @return string The version.
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Retrieves the brands.
     *
     * @return array The brands.
     */
    public function getBrands()
    {
        return $this->brands;
    }

    /**
     * Retrieves the number of brands.
     *
     * @return int The number of brands.
     */
    public function countBrands()
    {
        return count($this->brands);
    }
    

    /**
     * Checks if a brand exists.
     *
     * @param string $brandName The name of the brand to check.
     * @param bool $findByDirectory (Optional) Whether to search by brand directory instead of brand name. Default is false.
     * @return bool Returns true if the brand exists, false otherwise.
     */
    public function isBrandExist($brandName, $findByDirectory = false)
    {
        foreach ($this->brands as $brand)
        {

            if ($findByDirectory == true && $brand->getDirectory() == $brandName)
            {
                return true;
            }
            elseif ($brand->getName() == $brandName)
            {
                return true;
            }
        }
        return false;
    }
    
    public function setBrandUpdate($brandName, $newStatus, $version = null, $findByDirectory = false)
    {
        foreach ($this->brands as $brand)
        {
            if ($findByDirectory == true && $brand->getDirectory() == $brandName)
            {
                $brand->setUpdate($newStatus, $version);
                return true;
            }
            elseif ($brand->getName() == $brandName)
            {
                $brand->setUpdate($newStatus, $version);
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieves the debug flag.
     *
     * @return bool The debug flag.
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Sets the debug flag.
     *
     * @param bool $debug The debug flag.
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }
}