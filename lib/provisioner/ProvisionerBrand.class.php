<?php
namespace FreePBX\modules\Endpointman\Provisioner;

require_once(__DIR__.'/../epm_system.class.php');
require_once('ProvisionerFamily.class.php');

class ProvisionerBrand
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

    private $debug          = false;
    private $system         = null;

    /**
     * Creates a new ProvisionerBrand object.
     *
     * @param string $name The name of the brand.
     * @param string $directory The directory of the brand.
     * @param array $jsonData The JSON data to import.
     */
    public function __construct($name, $directory, $jsonData = null, $debug = true)
    {
        $this->debug     = $debug;
        $this->name      = $name;
        $this->directory = $directory;

        $this->system = new \FreePBX\modules\Endpointman\epm_system();
        if (! empty($jsonData))
        {
            $this->importJSON($jsonData);
        }
    }

    /**
     * Imports the JSON data into the brand.
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
        if (!is_array($jsonData))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Invalid JSON data [%s]!"), __CLASS__));
        }
        elseif (empty($jsonData['data']['brands']))
        {
            if ($noException) { return false; }
            throw new \Exception(_("Invalid JSON data, missing 'brands' key"));
        }

        $this->update         = false;
        $this->update_version = '';

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
            $id            = $familyData['id']        ?? '';
            $name          = $familyData['name']      ?? '';
            $dir           = $familyData['directory'] ?? '';
            $last_modified = $familyData['last_modified'] ?? '';
            if (empty($id) || empty($name) || empty($dir) || empty($this->brand_id))
            {
                if ($this->debug)
                {
                    dbug(sprintf(_("Invalid familyData data, id '%s', name '%s', directory '%s', brand_id '%s'"), $id, $name, $dir, $this->brand_id));
                }
                continue;
            }
            $this->family_list[] = new ProvisionerFamily($id, $name, $dir, $last_modified, $this->brand_id);
        }
        return true;
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
        return $this->package;
    }

    /**
     * Retrieves the MD5 sum of the brand.
     *
     * @return string The MD5 sum of the brand.
     */
    public function getMD5Sum()
    {
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
        foreach ($this->family_list as $family)
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
        return $this->family_list;
    }

    /**
     * Retrieves the number of families in the family list.
     *
     * @return int The number of families in the family list.
     */
    public function countFamilyList()
    {
        return count($this->family_list);
    }

    /**
     * Retrieves the OUI list of the brand.
     *
     * @return array The OUI list of the brand.
     */
    public function getOUI()
    {
        return $this->oui;
    }

    /**
     * Retrieves the number of OUIs in the OUI list.
     *
     * @return int The number of OUIs in the OUI list.
     */
    public function countOUI()
    {
        return count($this->oui);
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
}