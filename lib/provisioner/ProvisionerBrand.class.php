<?php
namespace FreePBX\modules\Endpointman\Provisioner;

// require_once(__DIR__.'/../epm_system.class.php');
require_once('ProvisionerBase.class.php');
require_once('ProvisionerFamily.class.php');

class ProvisionerBrand extends ProvisionerBase
{
    private $name           = '';
    private $brand_id       = null;
    private $directory      = '';
    private $package        = '';
    private $md5sum         = '';
    private $last_modified  = '';
    private $changelog      = '';
    private $family_list    = [];
    private $oui            = [];

    private $update         = false;
    private $update_version = '';

    private $master_json    = null;

    /**
     * Creates a new ProvisionerBrand object.
     *
     * @param string $name The name of the brand.
     * @param string $directory The directory of the brand.
     * @param array $jsonData The JSON data to import.
     */
    public function __construct(?string $name, ?string $directory, $jsonData = null, bool $debug = true)
    {
        parent::__construct($debug);
        $this->name      = $name;
        $this->directory = $directory;
        if (! empty($jsonData))
        {
            $this->importJSON($jsonData);
        }
    }

    /**
     * Check if the JSON file exists.
     *
     * @return bool True if the JSON file exists, false otherwise.
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
     * @return string The JSON file or an empty string if the path base or directory is empty.
     */
    public function getJSONFile()
    {
        if (empty($this->getPathBase())|| empty($this->getDirectory()))
        {
            return null;
        }
        return $this->system->buildPath($this->getPathBase(), 'endpoint', $this->getDirectory(), "brand_data.json");
    }

    /**
     * Imports the JSON data into the brand.
     *
     * @param array|string|null $jsonData The JSON data to import, the path to the JSON file, or null to use the getJSONfile().
     * @param bool $noException Whether to throw an exception if the JSON data is invalid.
     * @param bool $importChildrens Whether to import the children of the brand.
     * @throws \Exception If the JSON data is invalid.
     */
    public function importJSON($jsonData = null, bool $noException = false, bool $importChildrens = true)
    {
        if (empty($jsonData)) 
        {
            if (empty($this->directory))
            {
                if ($noException) { return false; }
                throw new \Exception(sprintf(_("Empty directory [%s]!"), __CLASS__));
            }
            elseif (! $this->isJSONExist())
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
        if (!is_array($jsonData))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Invalid JSON data [%s]!"), __CLASS__));
        }
        elseif (empty($jsonData['data']['brands']))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Invalid JSON data, missing 'brands' key [%s]!"), __CLASS__));
        }


        $this->resetAllData(true);

        $this->name           = $jsonData['data']['brands']['name']          ?? $this->name      ?? '';
        $this->brand_id       = $jsonData['data']['brands']['brand_id']      ?? null;
        $this->directory      = $jsonData['data']['brands']['directory']     ?? $this->directory ?? '';
        $this->package        = $jsonData['data']['brands']['package']       ?? '';
        $this->md5sum         = $jsonData['data']['brands']['md5sum']        ?? '';
        $this->last_modified  = $jsonData['data']['brands']['last_modified'] ?? '';
        $this->changelog      = $jsonData['data']['brands']['changelog']     ?? '';
        $this->oui            = $jsonData['data']['brands']['oui_list']      ?? [];
        
        $this->family_list = [];
        foreach ($jsonData['data']['brands']['family_list'] ?? array() as $familyData)
        {
            $id            = $familyData['id']            ?? '';
            $name          = $familyData['name']          ?? '';
            $dir           = $familyData['directory']     ?? '';
            $last_modified = $familyData['last_modified'] ?? '';
            if (empty($id) || empty($name) || empty($dir) || empty($this->brand_id))
            {
                if ($this->debug)
                {
                    dbug(sprintf(_("Invalid familyData data, id '%s', name '%s', directory '%s', brand_id '%s'"), $id, $name, $dir, $this->brand_id));
                }
                continue;
            }

            // $new_family = new ProvisionerFamily($id, $name, $dir, $last_modified, $this->brand_id);
            $new_family = new ProvisionerFamily($id, $name, $dir, $last_modified);
            $new_family->setParent($this);
            $new_family->setPathBase($this->getPathBase());
            $new_family->setURLBase($this->getURLBase());
            if ($importChildrens)
            {
                $new_family->importJSON(null, $noException, $importChildrens);
            }
            $this->family_list[] = $new_family;
        }
        return true;
    }

    /**
     * Resets all the data in the brand
     * 
     * @param bool $noReplace Whether to replace the name and directory if they are not empty.
     * @return void
     */
    public function resetAllData(bool $noReplace = false)
    {
        $this->name           = ($noReplace && !empty($this->name))      ? $this->name      : '';
        $this->directory      = ($noReplace && !empty($this->directory)) ? $this->directory : '';

        $this->brand_id       = null;
        $this->package        = '';
        $this->md5sum         = '';
        $this->last_modified  = '';
        $this->changelog      = '';
        $this->family_list    = [];
        $this->oui            = [];
        $this->update         = false;
        $this->update_version = '';
    }
   
    /**
     * Retrieves the name of the brand.
     *
     * @return string The name of the brand.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Retrieves the raw name of the brand (directory).
     *
     * @return string The raw name of the brand.
     */
    public function getNameRaw()
    {
        return $this->getDirectory();
    }

    /**
     * Retrieves the ID of the brand.
     *
     * @return int The ID of the brand.
     */
    public function getBrandID()
    {
        return $this->brand_id;
    }

    /**
     * Determines if the brand ID is set.
     *
     * @return bool True if the brand ID is set, false otherwise.
     */
    public function isSetBrandID()
    {
        return !empty($this->brand_id);
    }

    /**
     * Retrieves the directory of the brand.
     *
     * @return string The directory of the brand.
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Retrieves the package of the brand.
     *
     * @return string The package of the brand.
     */
    public function getPackage()
    {
        if (empty($this->package))
        {
            return null;
        }
        return $this->package;
    }

    /**
     * Retrieves the MD5 sum of the brand.
     *
     * @return string The MD5 sum of the brand.
     */
    public function getMD5Sum()
    {
        if (empty($this->md5sum))
        {
            return '';
        }
        return $this->md5sum;
    }

    /**
     * Retrieves the last modified date of the brand.
     *
     * @return string The last modified date of the brand.
     */
    public function getLastModified()
    {
        return $this->last_modified;
    }

    /**
     * Retrieves the maximum last modified date from the family list and the brand.
     *
     * @return string The maximum last modified date.
     */
    public function getLastModifiedMax()
    {
        $data_return = "";
        foreach ($this->getFamilyList() as $family)
        {
            $data_return = max($data_return, $family->getLastModified() ?? null);
        }
        $data_return = max($data_return, $this->last_modified);
        return $data_return;
    }

    /**
     * Retrieves the changelog of the brand.
     *
     * @return string The changelog of the brand.
     */
    public function getChangelog()
    {
        return $this->changelog;
    }

    /**
     * Retrieves the family list of the brand.
     *
     * @return array The family list of the brand.
     */
    public function getFamilyList()
    {
        if (empty($this->family_list) || !is_array($this->family_list))
        {
            return [];
        }
        return $this->family_list;
    }

    /**
     * Retrieves the number of families in the family list.
     *
     * @return int The number of families in the family list.
     */
    public function countFamilyList()
    {
        return count($this->getFamilyList());
    }

    /**
     * Retrieves a family from the family list.
     *
     * @param string $id The ID of the family to retrieve.
     * @param bool $direcotry Whether to search by directory.
     * @return ProvisionerFamily|null The family from the family list or null if the family is not found.
     */
    public function getFamily($id = null, bool $direcotry = false)
    {
        if (! empty($id) && $this->countFamilyList() > 0)
        {
            foreach ($this->getFamilyList() as $family)
            {
                if ($direcotry && $family->getDirectory() == $id)
                {
                    return $family;
                }
                else if ($family->getFamilyId() == $id)
                {
                    return $family;
                }
            }
        }
        return null;
    }

    /**
     * Retrieves the OUI list of the brand.
     *
     * @return array The OUI list of the brand.
     */
    public function getOUI()
    {
        if (empty($this->oui) || !is_array($this->oui))
        {
            return [];
        }
        return $this->oui;
    }

    /**
     * Retrieves the number of OUIs in the OUI list.
     *
     * @return int The number of OUIs in the OUI list.
     */
    public function countOUI()
    {
        return count($this->getOUI());
    }

    /**
     * Retrieves the update flag.
     *
     * @return bool The update flag.
     */
    public function getUpdate()
    {
        return $this->update;
    }

    /**
     * Sets the update flag.
     *
     * @param bool $update The update flag.
     * @param string $version (Optional) The update version.
     */
    public function setUpdate($update, $version = null)
    {
        $this->update = $update;
        if (!is_null($version))
        {
            $this->update_version = $version;
        }
    }

    /**
     * Retrieves the update version.
     *
     * @return string The update version.
     */
    public function getUpdateVersion()
    {
        return $this->update_version;
    }

    /**
     * Sets the update version.
     *
     * @param string $update_version The update version.
     */
    public function setUpdateVersion($update_version)
    {
        $this->update_version = $update_version;
    }






    public function getPathPackageFile()
    {
        if(empty($this->getPathTempProvisioner()) || empty($this->getPackage()))
        {
            return null;
        }
        return $this->system->buildPath($this->getPathTemp(), $this->getPackage());
        // $path_package = $this->system->buildPath($temp_directory, $package);
    }

    public function isExistPackageFile()
    {
        if (empty($this->getPathPackageFile()))
        {
            return false;
        }
        return file_exists($this->getPathPackageFile());
    }

    public function getPathBrand()
    {
        if (empty($this->getPathEndPoint()) || empty($this->getDirectory()))
        {
            return null;
        }
        return $this->system->buildPath($this->getPathEndPoint(), $this->getDirectory());
    }



        

    /**
     * Retrieves the URL for the brand JSON.
     *
     * @return string The URL for the brand JSON.
     */
    public function getURLBrandJSON()
    {
        if(empty($this->getURLBase()) || empty($this->getDirectory()))
        {
            return null;
        }
        return $this->system->buildUrl($this->getURLBase(), $this->getDirectory(), $this->getDirectory().".json");
    }

    public function getURLPackage()
    {
        if(empty($this->getURLBase()) || empty($this->getDirectory()) || empty($this->getPackage()))
        {
            return null;
        }
        return $this->system->buildUrl($this->getURLBase(), $this->getDirectory(), $this->getPackage());
        // $url_package  = $this->system->buildUrl($this->epm->URL_UPDATE, $row['directory'], $package);
    }


    
		




    /**
     * Downloads the brand JSON.
     *
     * @param bool $showmsg Whether to show messages.
     * @param bool $noException Whether to throw an exception if the download fails.
     * @return bool True if the download was successful, false otherwise.
     * @throws \Exception If the download fails and $noException is false.
     */
    public function downloadBrand($showmsg = true, $noException = true, $reload = true)
    {
        $url  = $this->getURLBrandJSON();
        $file = $this->getJSONFile();

        if (empty($url) || empty($file))
        {
            if ($noException) { return false; }
            throw new \Exception(_("Empty URL or file!"));
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
                if ($noException) { return false; }
                throw $e;
            }
        }
        else
        {
            try
            {
                $result = $this->system->download_file($url, $file);
            }
            catch (\Exception $e)
            {
                if ($noException) { return false; }
                throw $e;
            }
        }

        if ($reload && $result)
        {
            // Reload the JSON data and import the children
            $this->importJSON(null, true, true);
        }
        return $result;
    }




    public function downloadPackage($showmsg = true, $noException = true)
    {
        $url  = $this->getURLPackage();
        $file = $this->getPathPackageFile();

        if (empty($url) || empty($file))
        {
            $msg_err = _("Empty URL or file!");
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
                if ($noException) { return false; }
                throw $e;
            }
        }
        else
        {
            try
            {
                $result = $this->system->download_file($url, $file);
            }
            catch (\Exception $e)
            {
                if ($noException) { return false; }
                throw $e;
            }
        }
        return $result;
    }

    /**
     * Extract the brand package.
     *
     * @param bool $noException Whether to throw an exception if the extraction fails.
     * @param bool $remove_package Whether to remove the package file after extraction.
     * @return bool True if the extraction was successful, false otherwise.
     */
    public function extractPackage($noException = true, $remove_package = true)
    {
        $file_package = $this->getPathPackageFile();
        $path_temp    = $this->getPathTempProvisioner();

        if (empty($file_package) || empty($path_temp))
        {
            $msg_err = _("Empty temp file or brand directory!");
            if ($noException) { return false; }
            throw new \Exception($msg_err);
        }

        try
		{
            // Remove the previous extract folder if it exists otherwise the extract will fail
            $this->removePackageExtract(true);
			$uncompress = $this->system->decompressTarGz($file_package, $path_temp);
            
            
		    //Update File in the temp directory
		    //TODO: ????????? Why is this here?  file is ignored in proces movePackageExtracted!!!! Is not necessary to copy the file to the temp directory.
            // $brand_json_src  = $this->system->buildPath($this->isPathEndPointBrandExist($this->getDirectory(), false), "brand_data.json");
            // $brand_json_dest = $this->system->buildPath($this->isPathEndPointBrandExist($this->getDirectory(), true), "brand_data.json");
            // if (file_exists($brand_json_src))
            // {
            //     copy($brand_json_src, $brand_json_dest);
            // }

            return $uncompress;
		}
		catch (\Exception $e)
		{
            if ($noException) { return false; }
            throw $e;
		}
		finally
		{
            if ($remove_package)
            {
                $this->removePackageFile(true);
            }
		}
    }


    public function isPackageExtractFolderExist()
    {
        if (empty($this->getDirectory()))
        {
            return false;
        }
        return $this->isPathEndPointBrandExist($this->getDirectory(), true);
    }


    /**
     * Remove the extract folder of the brand package if it exists and is not empty (temp folder).
     *
     */
    public function removePackageExtract()
    {
        $path_temp_extract_brand = $this->getPathEndPointBrand($this->getDirectory(), true);
        if(empty($path_temp_extract_brand))
        {
            return false;
        }
        if ($this->isPackageExtractFolderExist())
        {
            // Caution: Second parameter must be true (folder temp)!
            $this->system->rmrf($path_temp_extract_brand);
        }
        if (file_exists($path_temp_extract_brand))
        {
            return false;
        }
        return true;
    }


    /**
     * Remove the package file of the brand if it exists.
     *
     * @param bool $noException Whether to throw an exception if the package file does not exist.
     * @return bool True if the package file was removed, false otherwise.
     * @throws \Exception If the package file does not exist and $noException is false.
     * @throws \Exception If the package file could not be removed.
     * @throws \Exception If the package file is not set.
     * @throws \Exception If the package file does not exist.
     */
    public function removePackageFile($noException = true)
    {
        $file_package = $this->getPathPackageFile();

        if (empty($file_package))
        {
            if ($noException) { return false; }
            throw new \Exception(_("Not set temp Package file!"));
        }
        if (file_exists($file_package))
        {
            if (unlink($file_package))
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the MD5 sum of the package file is correct.
     *
     * @param bool $noException Whether to throw an exception if the MD5 sum is incorrect.
     * @param bool $remove_package Whether to remove the package file if the MD5 sum is incorrect.
     * @return bool True if the MD5 sum is correct, false otherwise.
     * @throws \Exception If the MD5 sum is incorrect and $noException is false.
     * @throws \Exception If the temp file is not set or does not exist.
     */
    public function checkMD5PackageFile($noException = true, $remove_package = true)
    {
        $file_package = $this->getPathPackageFile();
        $md5sum       = $this->getMD5Sum();

        if (empty($md5sum) || empty($file_package) || !file_exists($file_package))
        {
            if ($noException) { return false; }
            throw new \Exception(_("Empty MD5sum or temp file not exist or not set!"));
        }

        $md5_pkg = md5_file($file_package);
        if ($md5sum != $md5_pkg)
        {
            if ($remove_package)
            {
                $this->removePackageFile();
            }
			return false;
        }
        return true;
    }



    public function movePackageExtracted($remote, ?array $errors_move = array(), bool $noException = true)
    {
        $filesToSkip = ['brand_data.json'];
        $src_tmp     = $this->getPathEndPointBrand($this->getDirectory(), true);
        $dest        = $this->getPathBrand();
        $permission  = 0755;

        if (empty($src_tmp) || empty($dest))
        {
            if ($noException) { return false; }
            throw new \Exception(_("Empty temp file or brand directory!"));
        }

        if (! file_exists($src_tmp))
        {
            if ($noException) { return false; }
            throw new \Exception(_("Source directory not exist!"));
        }

        if (!file_exists($dest))
        {
            mkdir($dest, $permission, true);
        }
        else
        {
            chmod($dest, $permission);
        }

        $dir_iterator = new \RecursiveDirectoryIterator($src_tmp, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator 	  = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file)
        {
            $destPath = $this->system->buildPath($dest, $iterator->getSubPathName());

            if ($file->isDir())
            {
                if (!file_exists($destPath))
                {
                    mkdir($destPath, $permission, true);
                }
                else
                {
                    chmod($destPath, $permission);
                }
            }
            else
            {
                if (in_array($file->getFilename(), $filesToSkip) || (!$remote) )
                {
                    if ($this->getDebug())
                    {
                        dbug("Skip: ".$file->getFilename());
                    }
                    continue;
                }

                if (rename($file, $destPath) === false)
                {
                    $errors_move[] = $file->getFilename();
                }
                else
                {
                    chmod($destPath, $permission);
                }
            }
            // outn('.');
        }

        return true;
    }




    public function uninstall()
    {
        $brand_name = $this->getDirectory();
        if (empty($brand_name))
        {
            return false;
        }

        $brand_dirs_full = array(
            $this->system->buildPath($this->getPathEndPoint(), $brand_name),
            $this->system->buildPath($this->getPathBase(), $brand_name)
        );
		foreach ($brand_dirs_full as $brand_dir_full)
        {
            if (file_exists($brand_dir_full))
            {
                $this->system->rmrf($brand_dir_full);
            }
        }
        return true;
    }




    public function generateJSON() {
        $data = [
            'data' => [
                'brands' => [
                    'name'          => $this->name          ?? '',
                    'brand_id'      => $this->brand_id      ?? '',
                    'directory'     => $this->directory     ?? '',
                    'package'       => $this->package       ?? '',
                    'md5sum'        => $this->md5sum        ?? '',
                    'last_modified' => $this->last_modified ?? '',
                    'changelog'     => $this->changelog     ?? '',
                    'oui_list'      => $this->getOUI(),
                    'family_list'   => []
                ]
            ]
        ];

        foreach ($this->family_list as $family)
        {
            $data['data']['brands']['family_list'][] = $family->generateJSON();
        }

        return $data;
    }
}