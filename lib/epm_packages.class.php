<?php
namespace FreePBX\modules\Endpointman;

require_once('epm_system.class.php');
require_once('provisioner/MasterJSON.class.php');

class Packages 
{
    public $epm       = null;
    public $system    = null;
    public $freepbx   = null;
    public $db        = null;
    public $config    = null;
    public $configepm = null;

    public $master_json = null;

    public function __construct($epm = null)
    {
        if (empty($epm))
        {
            throw new \Exception(_("Endpoint Manager object is required"));
        }
        elseif (! file_exists($epm->MODULE_PATH))
		{
            throw new \Exception(sprintf(_("[%s] Can't Load Local Endpoint Manager Directory!"), __CLASS__));
        }
		elseif (! file_exists($epm->PHONE_MODULES_PATH))
		{
            throw new \Exception(sprintf(_('[%s] Endpoint Manager can not create the modules folder!'), __CLASS__));
        }

        $this->epm       = $epm;            // Endpoint Manager object
        $this->freepbx   = $epm->freepbx;   // FreePBX object
		$this->db        = $epm->db;        // Database object configurate in the Endpoint Manager object
		$this->config    = $epm->config;    // Config object configurate in the Endpoint Manager object
        $this->system    = new epm_system();

        $this->reload_master_json();
    }

    public function reload_master_json()
    {
        $this->master_json = $this->readMasterJSON(true, true);
        if ($this->master_json != false)
        {

        }
        return $this->master_json;
    }

    /**
     * Retrieves the endpoint URL by concatenating the given URLs.
     *
     * @param string ...$urls The URLs to be concatenated.
     * @return string The concatenated endpoint URL.
     */
    public function buildURL(...$urls)
    {
        $url  = $this->epm->URL_UPDATE;
        $urls = array_filter($urls, function($url) { return !empty($url); });

        foreach ($urls as $u)
        {
            $url = $this->system->buildURL($url, $u);
        }
        return $url;
    }

    /**
     * Retrieves the path for a given option.
     *
     * @param mixed $option The option to retrieve the path for. (optional)
     * @return string The path for the given option.
     */
    public function getPath($option = null)
    {
        if (empty($option))
        {
            $option = "root_phone_module";
        }

        $data_return = "";
        switch($option)
        {
            case "phone_endpoint":
                $data_return = $this->system->buildPath($this->getPath(null), "endpoint");
                break;

            case "temp":
                $data_return = $this->system->buildPath($this->getPath(null), "temp");
                break;

            case "temp_provider":
                $data_return = $this->system->buildPath($this->getPath('temp'), "provisioner");
                break;

            case "json_master":
                $data_return = $this->system->buildPath($this->getPath("phone_endpoint"), "master.json");
                break;

            case "root_phone_module":
            default:
                $data_return = $this->epm->PHONE_MODULES_PATH;
        }
        return $data_return;
    }


    public function getBrandByDirectory(string $brand )
    {
        if (empty($this->master_json))
        {
            return null;
        }
		return $this->master_json->getBrand($brand, true);
    }

    public function getProductByProductID(int $product_id )
    {
        if (! empty($this->master_json) && ! empty($product_id) && is_numeric($product_id))
        {
            foreach ($this->master_json->getBrands() as $brand)
            {
                foreach ($brand->getFamilyList() as $family)
                {
                    if (empty($family->getFamilyId()))
                    {
                        continue;
                    }
                    elseif ($family->getFamilyId() == $product_id)
                    {
                        return $family;
                    }
                }
            }
        }
        return null;
    }



    public function readMasterJSON($load = false, $noException = false)
    {
        try
        {
            $master_path = $this->system->buildPath($this->getPath("phone_endpoint"), "master.json");
            if (! file_exists($master_path))
            {
                $load = false;
            }
            $master_json = new Provisioner\MasterJSON(null, $noException);

            $master_json->setPathBase($this->getPath("root_phone_module"));
            $master_json->setURLBase($this->epm->URL_UPDATE);
            $master_json->setJSONFile($master_path);

            if ($load)
            {
                $master_json->importJSON(null, $noException, true);
                // foreach ($master_json->getBrands() as &$brand)
                // {
                //     // Check if the brand file exists and is not exist then skip the brand
                //     if (! $brand->isJSONExist())
                //     {
                //         continue;
                //     }

                //     //If is necessary return more info in the exception (set the second parameter to false)
                //     if (! $brand->importJSON(null, true))
                //     {
                //         continue;
                //     }

                //     foreach ($brand->getFamilyList() as &$family)
                //     {
                //         // Check if the brand file exists and is not exist then skip the brand
                //         if (! $family->isJSONExist())
                //         {
                //             continue;
                //         }

                //         //If is necessary return more info in the exception (set the second parameter to false)
                //         if (! $family->importJSON(null, true))
                //         {
                //             continue;
                //         }
                //     }
                // }
            }
        }
        catch (\Exception $e)
        {
            throw $e;
        }
        return $master_json;
    }

    public function readFamilyJSON($json_file = null, $brand_id = null)
    {
        if (empty($json_file))
        {
            throw new \Exception(_("Family JSON file is required"));
        }
        try
        {
            $data_json = new Provisioner\ProvisionerFamily(null, null, null, null, $brand_id, $json_file);
        }
        catch (\Exception $e)
        {
            throw $e;
        }
        return $data_json;
    }



}