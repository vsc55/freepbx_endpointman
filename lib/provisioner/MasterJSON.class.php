<?php
namespace FreePBX\modules\Endpointman\Provisioner;

// require_once(__DIR__.'/../epm_system.class.php');
require_once('ProvisionerBase.class.php');
require_once('ProvisionerBrand.class.php');

class MasterJSON extends ProvisionerBase
{
    private $lastModified = null;
    private $package      = null;
    private $version      = '';
    private $brands       = [];

    private $json_file    = null;
    
    public function __construct($jsonData = null, bool $noException = false, bool $debug = true)
    {
        parent::__construct($debug);
        if (! empty($jsonData))
        {
            $this->importJSON($jsonData, $noException);
        }
    }

    /**
     * Retrieves the JSON file.
     *
     * @return string The JSON file.
     */
    public function getJSONFile()
    {
        if(empty($this->json_file))
        {
            return null;
        }
        return $this->json_file;
    }

    /**
     * Sets the JSON file.
     *
     * @param string $json_file The JSON file.
     */
    public function setJSONFile(string $json_file)
    {
        $this->json_file = $json_file;
    }

    /**
     * Checks if the JSON file exists.
     *
     * @return bool Returns true if the JSON file exists, false otherwise.
     */
    public function isJSONFileExist()
    {
        if(empty($this->json_file))
        {
            return false;
        }
        return file_exists($this->json_file);
    }


    /**
     * Imports the JSON data into the master JSON.
     *
     * @param array|string $jsonData The JSON data to import.
     * @param bool $noException Whether to throw an exception if the JSON data is invalid.
     * @throws \Exception If the JSON data is invalid.
     */
    public function importJSON($jsonData = null, bool $noException = false, bool $importChildrens = true)
    {
        if (empty($jsonData) && empty($this->json_file))
        {
            if ($noException) { return false; }
            throw new \Exception(_("Empty JSON data and JSON file!"));
        }
        elseif (empty($jsonData) && !empty($this->json_file) && !file_exists($this->json_file))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("JSON file '%s' does not exist!"), $this->json_file));
        }
        elseif (empty($jsonData) && !empty($this->json_file))
        {
            $jsonData = $this->json_file;
        }

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
            $new_brand = new ProvisionerBrand($name, $dir);
            $new_brand->setParent($this);
            $new_brand->setPathBase($this->getPathBase());
            $new_brand->setURLBase($this->getURLBase());
            if ($importChildrens)
            {
                $new_brand->importJSON(null, $noException, $importChildrens);
            }
            $this->brands[] = $new_brand;
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
        if (empty($this->package))
        {
            return null;
        }
        return $this->package;
    }

    // /**
    //  * Retrieves the version.
    //  *
    //  * @return string The version.
    //  */
    // public function getVersion()
    // {
    //     return $this->version;
    // }

    /**
     * Retrieves the brands.
     *
     * @return array The brands.
     */
    public function getBrands()
    {
        if(empty($this->brands) || !is_array($this->brands))
        {
            return [];
        }
        return $this->brands;
    }

    /**
     * Retrieves the number of brands.
     *
     * @return int The number of brands.
     */
    public function countBrands()
    {
        if (empty($this->brands) || !is_array($this->brands))
        {
            return 0;
        }
        return count($this->brands);
    }
    
    /**
     * Retrieves the last modified maximum for each brand.
     *
     * @return array An associative array where the keys are the brand directories and the values are the last modified maximum values.
     */
    public function getLastModifiedMaxBrands()
    {
        $data_return = array();
        foreach ($this->getBrands() as $brand)
        {
            $data_return[$brand->getDirectory()] = $brand->getLastModifiedMax() ?? '';
        }
        return $data_return;
    }

    /**
     * Checks if a brand exists.
     *
     * @param string $brandName The name of the brand to check.
     * @param bool $findByDirectory (Optional) Whether to search by brand directory instead of brand name. Default is false.
     * @return bool Returns true if the brand exists, false otherwise.
     */
    public function isBrandExist(string $brandName, bool $findByDirectory = false)
    {
        foreach ($this->getbrands() as $brand)
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

    /**
     * Retrieves a brand.
     *
     * @param string $brandName The name of the brand to retrieve.
     * @param bool $findByDirectory (Optional) Whether to search by brand directory instead of brand name. Default is false.
     * @return ProvisionerBrand|null The brand if found, null otherwise.
     */
    public function getBrand(string $brandName, bool $findByDirectory = false)
    {
        foreach ($this->brands as &$brand)
        {
            if ($findByDirectory == true && $brand->getDirectory() == $brandName)
            {
                return $brand;
            }
            elseif ($brand->getName() == $brandName)
            {
                return $brand;
            }
        }
        return null;
    }

    /**
     * Sets the update status for a brand.
     *
     * @param string $brandName The name of the brand to update.
     * @param bool $newStatus The new update status.
     * @param string $version (Optional) The version to update to. Default is null.
     * @param bool $findByDirectory (Optional) Whether to search by brand directory instead of brand name. Default is false.
     * @return bool Returns true if the brand was updated, false otherwise.
     */
    public function setBrandUpdate(string $brandName, bool $newStatus, ?string $version = null, bool $findByDirectory = false)
    {
        foreach ($this->brands as &$brand)
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
     * Retrieves the path for the package.
     *
     * @return string|null The path for the package or null if the package or the temp provider path is not set.
     */
    public function getPathPackage()
    {
        if (empty($this->getPackage()) || empty($this->getPathTempProvisioner()))
        {
            return null;
        }
        return $this->system->buildPath($this->getPathTempProvisioner(), $this->getPackage());
    }

    /**
     * Checks if the package file exists.
     *
     * @return bool Returns true if the package file exists, false otherwise.
     */
    public function isFilePackageExist()
    {
        if (empty($this->getPackage()))
        {
            return false;
        }
        return file_exists($this->getPathPackage());
    }

    /**
     * Retrieves the URL for the master JSON.
     *
     * @return string|null The URL for the master JSON or null if the URL base is not set.
     */
    public function getURLMaster()
    {
        if (empty($this->url_base))
        {
            return null;
        }
        return $this->system->buildUrl($this->url_base, "master.json");
    }

    /**
     * Retrieves the URL for the package.
     *
     * @return string|null The URL for the package or null if the URL base or the package is not set.
     */
    public function getURLPackage()
    {
        if (empty($this->url_base) || empty($this->package))
        {
            return null;
        }
        return $this->system->buildUrl($this->url_base, $this->package);
    }
    

    /**
     * Downloads the master JSON.
     *
     * @param bool $showmsg (Optional) Whether to show messages. Default is true.
     * @param bool $noException (Optional) Whether to throw an exception if the download fails. Default is true.
     * @return bool|string True if the download was successful, false otherwise.
     * @throws \Exception If the download fails and $noException is false.
     */
    public function downloadMaster(bool $showmsg = true, bool $noException = true)
    {
        $url  = $this->getURLMaster();
        $file = $this->getJSONFile();

        if (empty($url) || empty($file))
        {
            $msg_err = _("Empty URL or file!");
            if ($showmsg)
            {
                out($msg_err);
            }
            if ($noException) { return false; }
            throw new \Exception($msg_err);
        }
        $result = false;
        if ($showmsg)
        {
            try
            {
                $result = $this->system->download_file_with_progress_bar($url, $file);
            }
            catch (\Exception $e)
            {
                $msg_err = "❌ ". $e->getMessage();
                if ($noException) { return false; }
                throw new \Exception($msg_err);
            }
        }
        else
        {
            $result = $this->system->download_file($url, $file);
        }
        return $result;
    }

    /**
     * Downloads the package.
     *
     * @param string $local_version (Optional) The local version of the package.
     * @param bool $force (Optional) Whether to force the download. Default is false.
     * @param bool $noException (Optional) Whether to throw an exception if the download fails. Default is true.
     * @return bool|string The last modified date of the package if the download was successful, false otherwise.
     * @throws \Exception If the download fails and $noException is false.
     */
    public function downloadPackage(string $local_version = null, bool $force = false, bool $noException = true)
    {
        $url                  = $this->getURLPackage();
        $file                 = $this->getJSONFile();
        $path_package         = $this->getPathPackage();
        $path_package_extract = $this->getPathTempProvisionerNet();

        if (empty($url) || empty($file) || empty($path_package) || empty($path_package_extract))
        {
            $msg_err = _("Empty URL, file, package or extract path!");
            if ($noException) { return false; }
            throw new \Exception($msg_err);
        }
        $data_return = false;

        if ($force == true OR (empty($local_version)) OR ($local_version <= $this->getLastModified()))
        {
            //TODO: ERROR Pakage file not exist in repository GitHub.
            $result = $this->system->download_file($url, $path_package);
            try
            {
                $this->system->rmrf($path_package_extract);
                if ($this->system->decompressTarGz($path_package, $path_package_extract))
                {
                    if ($this->system->copyResource($path_package_extract, $this->getPathBase(), 0755, true))
                    {
                        $data_return = $this->getLastModified();
                    }
                }
            }
            catch (\Exception $e)
            {
                $msg_err = _("❌ ". $e->getMessage());
                dbug($msg_err);
                if ($noException) { return false; }
                throw new \Exception($msg_err);
            }
            finally
            {
                $this->system->rmrf($path_package_extract);
            }
        }
        return $data_return;
    }


    /**
     * Generates the JSON data.
     *
     * @param bool $show_data_brands (Optional) Whether to show the data for each brand. Default is true.
     * @param bool $arrayMode (Optional) Whether to return the data as an array. Default is false.
     * @return string|array The JSON data or array if $arrayMode is true.
     */
    public function generateJSON(bool $show_data_brands = true, bool $arrayMode = false)
    {
        $data_return = array(
            'last_modified' => $this->getLastModified() ?? '',
            'package'       => $this->getPackage() ?? '',
            'brands'        => [],
            'brands_data'   => []
        );
        foreach ($this->getBrands() as $brand)
        {
            $data_return['brands'][] = array(
                'name'      => $brand->getName()      ?? '',
                'directory' => $brand->getDirectory() ?? ''
            );
            if ($show_data_brands)
            {
                $data_return['brands_data'][] = $brand->generateJSON();
            }
        }
        if (! $arrayMode)
        {
            $data_return = json_encode(['data' => $data_return], JSON_PRETTY_PRINT);
        }
        return $data_return;
    }



}