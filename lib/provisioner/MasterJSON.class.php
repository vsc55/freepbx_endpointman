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

    private $json_file    = null;
    private $path_base     = null;
    private $url_base     = null;

    private $debug        = false;
    private $system       = null;

    /**
     * Creates a new MasterJSON object.
     *
     * @param array|string $jsonData The JSON data to import.
     * @param bool $debug Whether to enable debugging.
     */
    public function __construct($jsonData = null, $noException = false, $debug = true)
    {
        $this->debug  = $debug;
        $this->system = new \FreePBX\modules\Endpointman\epm_system();
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
        return $this->json_file;
    }

    /**
     * Sets the JSON file.
     *
     * @param string $json_file The JSON file.
     */
    public function setJSONFile($json_file)
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
        return file_exists($this->json_file);
    }


    /**
     * Imports the JSON data into the master JSON.
     *
     * @param array|string $jsonData The JSON data to import.
     * @param bool $noException Whether to throw an exception if the JSON data is invalid.
     * @throws \Exception If the JSON data is invalid.
     */
    public function importJSON($jsonData = null, $noException = false)
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
            $new_brand->setMasterJSON($this);
            $new_brand->setPathBase($this->getPathBase());
            $new_brand->setURLBase($this->getURLBase());
            $new_brand->setJSONFile($this->system->buildPath($this->getPathEndPoint(), $dir, "brand_data.json"));
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
     * Retrieves the last modified maximum for each brand.
     *
     * @return array An associative array where the keys are the brand directories and the values are the last modified maximum values.
     */
    public function getLastModifiedMaxBrands()
    {
        $data_return = array();
        foreach ($this->brands as $brand)
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

    /**
     * Retrieves a brand.
     *
     * @param string $brandName The name of the brand to retrieve.
     * @param bool $findByDirectory (Optional) Whether to search by brand directory instead of brand name. Default is false.
     * @return ProvisionerBrand|null The brand if found, null otherwise.
     */
    public function getBrand($brandName, $findByDirectory = false)
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
    public function setBrandUpdate($brandName, $newStatus, $version = null, $findByDirectory = false)
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





    public function getPathBase()
    {
        return $this->path_base;
    }

    public function setPathBase($path_base)
    {
        $this->path_base = $path_base;
    }

    public function isPathBaseExist()
    {
        return file_exists($this->path_base);
    }

    public function isPathBaseWritable()
    {
        return is_writable($this->path_base);
    }

    public function getPathEndPoint()
    {
        return $this->system->buildPath($this->path_base, "endpoint");
    }

    public function getPathTemp()
    {
        return $this->system->buildPath($this->path_base, "temp");
    }

    public function getPathTempProvider()
    {
        return $this->system->buildPath($this->getPathTemp(), "provisioner");
    }


    public function getPathPackage()
    {
        return $this->system->buildPath($this->getPathTempProvider(), $this->getPackage());
    }

    public function isFilePackageExist()
    {
        return file_exists($this->getPathPackage());
    }








    public function getURLBase()
    {
        return $this->url_base;
    }

    public function setURLBase($url_base)
    {
        $this->url_base = $url_base;
    }

    public function isURLBaseExist()
    {
        return ! empty($this->url_base);
    }

    public function getURLMaster()
    {
        return $this->system->buildUrl($this->url_base, "master.json");
    }

    public function getURLPackage()
    {
        return $this->system->buildUrl($this->url_base, $this->package);
    }
    

    public function downloadMaster($showmsg = true, $noException = true)
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


    public function downloadPackage($local_version = null, $force = false, $noException = true)
    {
        $url                  = $this->getURLPackage();
        $file                 = $this->getJSONFile();
        $path_package         = $this->getPathPackage();
        $path_package_extract = $this->system->buildPath($this->getPathTempProvider(), "provisioner_net");

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




}