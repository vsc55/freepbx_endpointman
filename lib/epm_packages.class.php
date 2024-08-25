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
        $this->configepm = $epm->configmod; // Config Endpoint Manager
        $this->system    = new epm_system();
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



    public function readMasterJSON()
    {
        $json = $this->getPath('json_master');
        try
        {
            $master_json = new Provisioner\MasterJSON($json);
        }
        catch (\Exception $e)
        {
            throw $e;
        }
        return $master_json;
    }


}