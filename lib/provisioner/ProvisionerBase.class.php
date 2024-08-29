<?php
namespace FreePBX\modules\Endpointman\Provisioner;

require_once(__DIR__.'/../epm_system.class.php');

class ProvisionerBase
{
    protected $path_base = null;
    protected $url_base  = null;

    protected $debug     = false;
    protected $system    = null;

    protected $parent     = null;

    /**
     * Constructor.
     */
    public function __construct($debug = true)
    {
        $this->path_base = null;
        $this->url_base  = null;
        $this->parent    = null;
        $this->debug     = $debug;
        $this->system    = new \FreePBX\modules\Endpointman\epm_system();
    }

    public function importJSON($jsonData = null, bool $noException = false, bool $importChildrens = true)
    {
        
    }

    public function resetAllData(bool $noReplace = false)
    {
        
    }


    
    /**
     * Retrieves the parent.
     *
     * @return mixed The parent.
     */
    public function getParent()
    {
        return $this->parent;
    }
    
    /**
     * Sets the parent.
     *
     * @param mixed $new_parent The new parent.
     * @return mixed The new parent.
     */
    public function setParent($new_parent = null)
    {
        $this->parent = $new_parent;
        return $this->parent;
    }

    /**
     * Checks if the parent is set.
     *
     * @return bool True if the parent is set, false otherwise.
     */
    public function isParentSet()
    {
        return !empty($this->parent);
    }

    /**
     * Retrieves the base path.
     *
     * @return string The base path or null if it is not set.
     */
    public function getPathBase()
    {
        if (empty($this->path_base))
        {
            return null;
        }
        return $this->path_base;
    }

    /**
     * Sets the base path.
     *
     * @param string $path_base The base path.
     */
    public function setPathBase(?string $path_base)
    {
        $this->path_base = $path_base;
    }

    /**
     * Checks if the base path exists.
     *
     * @return bool True if the base path exists, false otherwise.
     */
    public function isPathBaseExist()
    {
        if (empty($this->getPathBase()))
        {
            return false;
        }
        return file_exists($this->getPathBase());
    }

    
    /**
     * Checks if the base path is writable.
     *
     * @return bool Returns true if the base path is writable, false otherwise.
     */
    public function isPathBaseWritable()
    {
        if(! $this->isPathBaseExist())
        {
            return false;
        }
        return is_writable($this->getPathBase());
    }

    /**
     * Retrieves the path for the endpoint directory.
     *
     * @return string|null The path for the endpoint directory or null if the base path is not set.
     */
    public function getPathEndPoint()
    {
        if(! $this->isPathBaseExist())
        {
            return null;
        }
        return $this->system->buildPath($this->getPathBase(), "endpoint");
    }








    /**
     * Retrieves the path for the endpoint brand directory or the temp path for the endpoint brand directory.
     *
     * @param string|null $brand The brand name for the endpoint brand directory.
     * @param bool $temp True if the temp path is requested, false otherwise.
     * @return string|null The path for the endpoint brand directory or null if the base path is not set or the brand is 
     *                     empty or the temp path is requested and the temp path is not set.
     */
    public function getPathEndPointBrand(?string $brand = null, bool $temp = false)
    {
        if (empty($brand))
        {
            return null;
        }
        else if ($temp && empty($this->getPathTempProvisioner()))
        {
            return null;
        }
        else if (! $temp && empty($this->getPathEndPoint()))
        {
            return null;
        }
        $root_path = $temp ? $this->getPathTempProvisioner() : $this->getPathEndPoint();
        return $this->system->buildPath($root_path, $brand);
    }

    public function isPathEndPointBrandExist(?string $brand = null, bool $temp = false)
    {
        $path = $this->getPathEndPointBrand($brand, $temp);
        if (empty($path))
        {
            return false;
        }
        return file_exists($path);
    }
    






    /**
     * Retrieves the path for the temp directory.
     *
     * @return string|null The path for the temp directory or null if the base path is not set.
     */
    public function getPathTemp()
    {
        if(! $this->isPathBaseExist())
        {
            return null;
        }
        return $this->system->buildPath($this->getPathBase(), "temp");
    }

    /**
     * Checks if the temp path exists.
     *
     * @return bool True if the temp path exists, false otherwise.
     */
    public function isPathTempExist()
    {
        if (empty($this->getPathTemp()))
        {
            return false;
        }
        return file_exists($this->getPathTemp());
    }

    /**
     * Checks if the temp path is writable.
     *
     * @return bool Returns true if the temp path is writable, false otherwise.
     */
    public function isPathTempWritable()
    {
        if(! $this->isPathTempExist())
        {
            return false;
        }
        return is_writable($this->getPathTemp());
    }


    public function getPathTempProvisioner()
    {
        if(! $this->isPathTempExist())
        {
            return null;
        }
        return $this->system->buildPath($this->getPathTemp(), 'provisioner');
    }


    /**
     * Retrieves the path for the temp provider net.
     *
     * @return string|null The path for the temp provider net or null if the temp provider path is not set.
     */
    public function getPathTempProvisionerNet()
    {
        if (empty($this->getPathTempProvisioner()))
        {
            return null;
        }
        return $this->system->buildPath($this->getPathTempProvisioner(), "provisioner_net");
    }


















    /**
     * Retrieves the base URL.
     *
     * @return string The base URL or null if it is not set.
     */
    public function getURLBase()
    {
        if (empty($this->url_base))
        {
            return null;
        }
        return $this->url_base;
    }

    /**
     * Sets the base URL.
     *
     * @param string $url_base The base URL.
     */
    public function setURLBase(?string $url_base)
    {
        $this->url_base = $url_base;
    }

    /**
     * Checks if the base URL exists.
     *
     * @return bool True if the base URL exists, false otherwise.
     */
    public function isURLBaseExist()
    {
        return ! empty($this->getURLBase());
    }

    /**
     * Retrieves the debug flag.
     *
     * @return bool The debug flag.
     */
    public function getDebug()
    {
        if (empty($this->debug))
        {
            return false;
        }
        return $this->debug;
    }

    /**
     * Sets the debug flag.
     *
     * @param bool $debug The debug flag.
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
    }
}