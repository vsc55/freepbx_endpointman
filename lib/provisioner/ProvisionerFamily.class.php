<?php
namespace FreePBX\modules\Endpointman\Provisioner;

require_once(__DIR__.'/../epm_system.class.php');
require_once('ProvisionerModel.class.php');

class ProvisionerFamily
{
    private $id                  = null;
    private $brand_id            = null;
    private $name                = '';
    private $directory           = '';
    private $changelog           = '';
    private $last_modified       = '';
    private $firmware_ver        = '';
    private $firmware_pkg        = 'NULL';
    private $firmware_md5sum     = '';
    private $description         = '';
    private $configuration_files = '';
    private $provisioning_types  = [];
    private $model_list          = [];
    
    private $json_file           = null;
    private $debug               = false;
    private $system              = null;


    public function __construct($id = null, $name = null, $directory = null, $last_modified = null, $brand_id = null, $jsonData = null, $debug = true)
    {
        $this->debug  = $debug;
        $this->system = new \FreePBX\modules\Endpointman\epm_system();

        if (!empty($jsonData))
        {
            $this->importJSON($jsonData);
        }
        else
        {
            $this->id            = $id;
            $this->name          = $name;
            $this->directory     = $directory;
            $this->last_modified = $last_modified;
            $this->brand_id      = $brand_id;
        }        
    }

    /**
     * Retrieves the JSON file.
     *
     * @return string The JSON file.
     */
    public function isJSONExist()
    {
        return file_exists($this->json_file);
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
        if (!is_array($jsonData['data']))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Invalid JSON data [%s]!"), __CLASS__));
        }
        
        $this->id                  = $jsonData['data']['id']                  ?? $this->id            ?? null;
        $this->name                = $jsonData['data']['name']                ?? $this->name          ?? '';
        $this->directory           = $jsonData['data']['directory']           ?? $this->directory     ?? '';
        $this->changelog           = $jsonData['data']['changelog']           ?? '';
        $this->last_modified       = $jsonData['data']['last_modified']       ?? $this->last_modified ?? '';
        $this->firmware_ver        = $jsonData['data']['firmware_ver']        ?? '';
        $this->firmware_pkg        = $jsonData['data']['firmware_pkg']        ?? 'NULL';
        $this->firmware_md5sum     = $jsonData['data']['firmware_md5sum']     ?? '';
        $this->description         = $jsonData['data']['description']         ?? '';
        $this->configuration_files = $jsonData['data']['configuration_files'] ?? [];
        $this->provisioning_types  = $jsonData['data']['provisioning_types']  ?? [];

        if (!is_string($this->configuration_files))
        {
            $this->configuration_files = explode(',', $this->configuration_files);
        }
        else
        {
            $this->configuration_files = [];
        }
        
        $this->model_list = [];
        foreach ($jsonData['data']['model_list'] ?? array() as $modelData)
        {
            $id        = $modelData['id']            ?? null;
            $model     = $modelData['model']         ?? '';
            $lines     = $modelData['lines']         ?? '0';
            $template  = $modelData['template_data'] ?? [];
            $brand_id  = $this->brand_id;
            $family_id = $this->id;

            if (empty($id) || empty($model) || empty($template) || empty($brand_id) || empty($family_id))
            {
                if ($this->debug)
                {
                    dbug(sprintf(_("Invalid model data, id '%s', model '%s'"), $id, $model));
                }
                continue;
            }
            $this->model_list[] = new ProvisionerModel($id, $model, $lines, $template, $brand_id, $family_id);
        }
        return true;   
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBrandId()
    {
        return $this->brand_id;
    }

    public function getFamilyId()
    {
        return  sprintf("%s%s", $this->brand_id, $this->id);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getShortName()
    {
        return preg_replace("/\[(.*?)\]/si", "", $this->name);
    }

    public function getDirectory()
    {
        return $this->directory;
    }

    public function getChangelog()
    {
        return $this->changelog;
    }

    public function getLastModified()
    {
        return $this->last_modified;
    }

    public function getFirmwareVer()
    {
        return $this->firmware_ver;
    }

    public function getFirmwarePkg()
    {
        return $this->firmware_pkg;
    }

    public function getFirmwareMd5sum()
    {
        return $this->firmware_md5sum;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getConfigurationFiles()
    {
        return $this->configuration_files;
    }

    public function getProvisioningTypes()
    {
        return $this->provisioning_types;
    }

    public function getModelList()
    {
        return $this->model_list;
    }

    public function isModelExist($modelName)
    {
        if (! empty($modelName))
        {
            foreach ($this->model_list as $model)
            {

                if ($model->getModel() == $modelName)
                {
                    return true;
                }
            }
        }
        return false;
    }

    public function getModel($modelName)
    {
        if (! empty($modelName))
        {
            foreach ($this->model_list as $model)
            {
                if ($model->getModel() == $modelName)
                {
                    return $model;
                }
            }
        }
        return null;
    }

    public function getModelTemplate($modelName)
    {
        if (! empty($modelName))
        {
            foreach ($this->model_list as $model)
            {
                if ($model->getModel() == $modelName)
                {
                    return $model->getTemplate();
                }
            }
        }
        return [];
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