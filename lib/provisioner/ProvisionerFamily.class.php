<?php
namespace FreePBX\modules\Endpointman\Provisioner;

require_once('ProvisionerBase.class.php');
require_once('ProvisionerModel.class.php');

class ProvisionerFamily extends ProvisionerBase
{
    private $id                  = null;
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

    public function __construct($id = null, ?string $name = null, string $directory = '', ?string $last_modified = '', $jsonData = null, bool $debug = true)
    {
        parent::__construct($debug);

        $this->id            = $id;
        $this->name          = $name;
        $this->directory     = $directory;
        $this->last_modified = $last_modified;

        if (!empty($jsonData))
        {
            $this->importJSON($jsonData);
        }
    }

    /**
     * Retrieves the JSON file.
     *
     * @return string The JSON file.
     */
    public function isJSONExist()
    {
        if (empty($this->getJSONFile()))
        {
            return false;
        }
        return file_exists($this->getJSONFile());
    }

    /**
     * Retrieves the JSON file.
     *
     * @return string The JSON file.
     */
    public function getJSONFile()
    {
        if (empty($this->getBrandDirecotry()) || empty($this->directory))
        {
            return '';
        }
        return $this->system->buildPath($this->getPathBase(), 'endpoint', $this->getBrandDirecotry() , $this->getDirectory(), "family_data.json");
    }

    public function importJSON($jsonData = null, bool $noException = false, bool $importChildrens = true)
    {
        if (empty($jsonData)) 
        {
            if (empty($this->directory))
            {
                if ($noException) { return false; }
                throw new \Exception(sprintf(_("Empty directory [%s]!"), __CLASS__));
            }
            elseif (empty($this->getBrandDirecotry()))
            {
                if ($noException) { return false; }
                throw new \Exception(sprintf(_("Empty parent brand directory [%s]!"), __CLASS__));
            }
            elseif (!$this->isJSONExist())
            {
                if ($noException) { return false; }
                throw new \Exception(sprintf(_("Empty JSON data and File '%s' not exist [%s]!"), $this->getJSONFile(), __CLASS__));
            }
            $jsonData = $this->getJSONFile();
        }

        if (is_string($jsonData))
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

        $this->resetAllData();
        
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

        if (is_string($this->configuration_files))
        {
            $this->configuration_files = explode(',', $this->configuration_files);
        }
        elseif (!is_array($this->configuration_files))
        {
            $this->configuration_files = [];
        }
        
        $this->model_list = [];
        foreach ($jsonData['data']['model_list'] ?? array() as $modelData)
        {
            $id        = $modelData['id']            ?? null;
            $model     = $modelData['model']         ?? '';
            $lines     = $modelData['lines']         ?? '1';
            $template  = $modelData['template_data'] ?? [];
            $brand_id  = $this->getBrandId();
            $family_id = $this->id;

            if (empty($id) || empty($model) || empty($brand_id) || empty($family_id))
            {
                if ($this->debug)
                {
                    dbug(sprintf(_("Invalid model data, id '%s', model '%s', brandid '%s', family_id '%s'!"), $id, $model, $brand_id, $family_id));
                }
                continue;
            }

            $template_data = [];
            foreach ($template as $file)
            {
                $template_data[$file] = [];
            }

            $new_model = new ProvisionerModel($id, $model, $lines, $template_data);
            $new_model->setParent($this);
            $new_model->setPathBase($this->getPathBase());
            $new_model->setURLBase($this->getURLBase());

            $this->model_list[] = $new_model;
        }
        return true;
    }

    /**
     * Resets all data to default values.
     */
    public function resetAllData(bool $noReplace = false)
    {
        parent::resetAllData($noReplace);
        
        $this->id             = ($noReplace && !empty($this->id))            ? $this->id            : '';
        $this->name           = ($noReplace && !empty($this->name))          ? $this->name          : '';
        $this->directory      = ($noReplace && !empty($this->directory))     ? $this->directory     : '';
        $this->last_modified  = ($noReplace && !empty($this->last_modified)) ? $this->last_modified : '';

        $this->name                = '';
        $this->changelog           = '';
        $this->firmware_ver        = '';
        $this->firmware_pkg        = 'NULL';
        $this->firmware_md5sum     = '';
        $this->description         = '';
        $this->configuration_files = '';
        $this->provisioning_types  = [];
        $this->model_list          = [];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBrandId()
    {
        if ($this->isParentSet())
        {
            return $this->getParent()->getBrandID();
        }
        return null;
    }
    
    public function getBrandDirecotry()
    {
        if ($this->isParentSet())
        {
            return $this->getParent()->getDirectory();
        }
        return null;
    }

    public function getFamilyId()
    {
        if (empty($this->getBrandId()) || empty($this->id))
        {
            return null;
        }
        return  sprintf("%s%s", $this->getBrandId(), $this->id);
        // return  sprintf("%s%s", $this->brand_id, $this->id);
    }

    public function getName()
    {
        if (empty($this->name))
        {
            return '';
        }
        return $this->name;
    }

    public function getNameRaw()
    {
        return $this->getDirectory();
    }

    public function getShortName()
    {

        return preg_replace("/\[(.*?)\]/si", "", $this->getName());
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

    public function getConfigurationFiles(bool $serialize = false, bool $serialize_json = false)
    {
        if ($serialize)
        {
            if ($serialize_json)
            {
                return json_encode($this->getConfigurationFiles());
            }
            return implode(",", $this->getConfigurationFiles());
        }
        if (empty($this->configuration_files) || !is_array($this->configuration_files))
        {
            return [];
        }
        return $this->configuration_files;
    }

    public function getProvisioningTypes()
    {
        return $this->provisioning_types;
    }

    public function getModelList()
    {
        if (empty($this->model_list) || !is_array($this->model_list))
        {
            return [];
        }
        return $this->model_list;
    }

    public function countModels()
    {
        return count($this->getModelList());
    }

    public function isModelExist(string $modelName)
    {
        if (! empty($modelName))
        {
            foreach ($this->getModelList() as $model)
            {
                if ($model->getModel() == $modelName)
                {
                    return true;
                }
            }
        }
        return false;
    }

    public function getModel(string $modelName)
    {
        if (! empty($modelName))
        {
            foreach ($this->getModelList() as $model)
            {
                if ($model->getModel() == $modelName)
                {
                    return $model;
                }
            }
        }
        return null;
    }

    public function getModelTemplateList(string $modelName)
    {
        if (! empty($modelName))
        {
            foreach ($this->getModelList() as $model)
            {
                if ($model->getModel() == $modelName)
                {
                    return $model->getTemplateList();
                }
            }
        }
        return [];
    }
    
    
    //TODO: Not necessary, this file is included in the brand package
    // public function getURLFamilyJSON()
    // {
    //     return "";
    //     // return $this->system->buildUrl($this->url_base, $this->directory, $this->directory.".json");
    // }


    /**
     * Retrieves the url to file package file is downloaded from.
     *
     * @return string The url to file package file is downloaded from.
     */
    public function getURLFirmwarePkg()
    {
        if (empty($this->getURLBase()) || empty($this->getBrandDirecotry()) || empty($this->getFirmwarePkg()))
        {
            return null;
        }
        return $this->system->buildUrl($this->getURLBase(), $this->getBrandDirecotry(), $this->getFirmwarePkg());
    }




    /**
     * Retrieves the path to the temporary provisioner directory.
     *
     * @return string The path to the temporary provisioner directory.
     */
    public function getPathFirmwarePkg()
    {
        // $this->system->buildPath($this->epm->PROVISIONER_PATH, $firmware_pkg);
        if (empty($this->getPathTempProvisioner()) || empty($this->getFirmwarePkg()))
        {
            return null;
        }
        return $this->system->buildPath($this->getPathTempProvisioner(), $this->getFirmwarePkg());
    }

    /**
     * Checks if the firmware package file exists.
     *
     * @return bool True if the firmware package exists, false otherwise.
     */
    public function isPathFirmwarePkgExist()
    {
        if (empty($this->getPathFirmwarePkg()))
        {
            return false;
        }
        return file_exists($this->getPathFirmwarePkg());
    }

    /**
     * Checks if the firmware package file is md5sum valid.
     *
     * @return bool True if the firmware package is valid, false otherwise.
     */
    public function isMD5SumFirmwarePkgValid()
    {
        if (empty($this->getFirmwareMd5sum()) || ! $this->isPathFirmwarePkgExist())
        {
            return false;
        }
        return md5_file($this->getPathFirmwarePkg()) == $this->getFirmwareMd5sum();
    }

    /**
     * Downloads the firmware package file from the URL and saves it to the path.
     * If the file is not downloaded successfully, it will be removed.
     *
     * @param bool $showmsg True to show the progress bar, false otherwise.
     * @param bool $noException True to not throw an exception, false otherwise.
     * @return bool True if the firmware package was downloaded, false otherwise.
     */
    public function downloadFirmwarePkg($showmsg = true, $noException = true)
    {
        $url_fw_pkg  = $this->getURLFirmwarePkg();
        $path_fw_pkg = $this->getPathFirmwarePkg();

        if (empty($url_fw_pkg) || empty($path_fw_pkg))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Empty URL or path for firmware package [%s]!"), __CLASS__));
        }
        if (empty($this->getFirmwarePkg()))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Empty firmware package name [%s]!"), __CLASS__));
        }

        $result = false;
        if ($showmsg)
        {
            try
            {
                $result = $this->system->download_file_with_progress_bar($url_fw_pkg, $path_fw_pkg);
            }
            catch (\Exception $e)
            {
                if ($noException) { return false; }
                throw $e;
            }
        }
        else
        {
            $result = $this->system->download_file($url_fw_pkg, $path_fw_pkg);
        }
        if(!$result)
        {
            $this->removeFirmwarePkg();
        }
        return $result;
    }

    /**
     * Removes the firmware package file if it exists.
     *
     * @return bool True if the firmware package was removed, false otherwise.
     */
    public function removeFirmwarePkg()
    {
        if (! $this->isPathFirmwarePkgExist())
        {
            return false;
        }
        return unlink($this->getPathFirmwarePkg());
    }

    public function installFirmwarePkg(string $destination, bool $noException = true, ?array &$copy_files = [])
    {
        if (! $this->isPathFirmwarePkgExist() || empty($destination))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Firmware package '%s' not exist or empty directory [%s]!"), $this->getFirmwarePkg(), __CLASS__));
            
        }
        if (empty($this->getBrandDirecotry()) || empty($this->getDirectory()))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Empty brand or family directory [%s]!"), __CLASS__));
        }
        if (! file_exists($destination))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Destination directory '%s' not exist [%s]!"), $destination, __CLASS__));
        }

        $path_brand = $this->system->buildPath($this->getPathTempProvisioner(), $this->getBrandDirecotry());
        $path_model = $this->system->buildPath($this->getPathTempProvisioner(), $this->getBrandDirecotry(), $this->getDirectory());
        $path_fw    = $this->system->buildPath($path_model, "firmware");

        if (file_exists($path_fw))
        {
            if (! $this->system->rmrf($path_fw))
            {
                if ($noException) { return false; }
                throw new \Exception(sprintf(_("Failed to remove old firmware directory '%s' [%s]!"), $path_fw, __CLASS__));
            }
        }
        if (! mkdir($path_fw, 0777, true) ) 
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Failed to create firmware directory '%s' [%s]!"), $path_fw, __CLASS__));
        }

        try
        {
            $this->system->decompressTarGz($this->getPathFirmwarePkg(), $path_model);
        }
        catch (\Exception $e)
        {
            if ($noException) { return false; }
            throw $e;
        }
        
        // $path_brand_dir    = $this->system->buildPath($temp_directory, $row['directory']);
        // $path_cfg_dir 	   = $this->system->buildPath($temp_directory, $row['directory'], $row['cfg_dir']);
        // $path_firmware_dir = $this->system->buildPath($temp_directory, $row['directory'], $row['cfg_dir'], "firmware");
		
        $copy_error = false;
		foreach (glob($this->system->buildPath($path_fw, "*")) as $src_path)
		{
			$file	   		   = basename($src_path);
			$dest_path 		   = $this->system->buildPath($destination, $file);
            $copy_file_result  = @copy($src_path, $dest_path);
			$copy_files[$file] = $copy_file_result;
            if (! $copy_file_result)
            {
                $copy_error = true;
            }
		}

        if (file_exists($path_brand))
        {
            $this->system->rmrf($path_brand);
        }

        return $copy_error;
    }




    public function generateJSON()
    {
        $conf_files = $this->configuration_files;

        if (is_array($conf_files))
        {
            if (empty($conf_files))
            {
                $conf_files = "";
            }
            else
            {
                $conf_files = implode(",", $conf_files);
            }
        }

        $data = [
            'id'                  => $this->id           ?? '',
            'name'                => $this->name         ?? '',
            'directory'           => $this->directory    ?? '',
            'changelog'           => $this->changelog    ?? '',
            // 'last_modified'       => $this->last_modified,
            'firmware_ver'        => $this->firmware_ver ?? '',
            'firmware_pkg'        => empty($this->firmware_pkg) ? 'NULL' : $this->firmware_pkg,
            'firmware_md5sum'     => $this->firmware_md5sum ?? '',
            'description'         => $this->description     ?? '',
            'configuration_files' => $conf_files,
            'provisioning_types'  => $this->provisioning_types ?? array(),
            'model_list'          => []
        ];
        foreach ($this->model_list as $model)
        {
            $data['model_list'][] = $model->generateJSON();
        }
        return $data;
    }


}