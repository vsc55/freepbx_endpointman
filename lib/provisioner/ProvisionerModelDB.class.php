<?php
namespace FreePBX\modules\Endpointman\Provisioner;

require_once('ProvisionerBaseDB.class.php');
require_once('ProvisionerBrandDB.class.php');
require_once('ProvisionerFamilyDB.class.php');

class ProvisionerModelDB extends ProvisionerBaseDB
{
    protected $id      = null;
    protected $family  = null;
	protected $destory = false;
    

	public function __construct($epm, $family = null, array $new = [], bool $debug = true)
    {
        parent::__construct($debug);
        if (! empty($epm))
        {
            $this->parent = $epm;
        }
        else
        {
            throw new \Exception(_("Parent object is required!"));
        }
        if (! empty($family))
        {
            $this->family = $family;
        }

        if (! empty($new))
        {
            return $this->create($new);
        }
    }
    
	/**
     * Checks if the object is destroyed.
     */
    public function isDestory()
    {
        return $this->destory;
    }

	 /**
     * Checks if the ID exists in the database.
     * 
     * @param int|null $id The ID to check.
     * @param bool $noException True to not throw an exception, false otherwise.
     * @return bool True if the ID exists, false otherwise.
     * @throws \Exception If the ID is required or the object is destroyed.
     * @throws \Exception If the ID does not exist.
     * @throws \Exception If the query fails.
     */
    public function isExistID(?int $id = null, bool $noException = true)
    {
        if ($this->destory)
        {
            if ($noException) { return false; }
            throw new \Exception(_("The object is destroyed!"));
        }
        if (empty($id))
        {
            $id = $this->id;
        }
        if (empty($id))
        {
            if ($noException) { return false; }
            throw new \Exception(_("The ID is required!"));
        }
        $count = $this->querySelect("endpointman_model_list", "COUNT(*) as total", $id, 1, true);
        if ($count === 0)
        {
            return false;
        }
        return true;
    }

	public function findBy($find, $value)
    {
        if (empty($find) || empty($value))
        {
            return false;
        }
        if (! $this->isParentSet())
        {
            return false;
        }
        if (! in_array($find, ['id', 'brand', 'model', 'max_lines', 'product_id', 'enabled', 'hidden']))
		{
			return false;
		}
        $where = [
            $find => [
                'operator' => "=", 
                'value' => $value
            ],
        ];

        $row = $this->querySelect("endpointman_model_list", "id, brand, product_id", $where, 1);
        if (empty($row))
        {
            return false;
        }
		
        $id        = $row['id'];
		$family_id = $row['product_id'] ?? null;
        if (empty($id))
        {
            return false;
        }
        $this->id = $id;

        if (! $this->isFamilySet())
        {
            $this->setFamily(is_numeric($family_id) ? $family_id : null);
        }

        return true;
    }





	public function create(array $new, bool $return_new_id = false, bool $noException = true)
    {
        if (! $this->isParentSet())
        {
            $msg = _("Parent object is not set!");
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        elseif (empty($new))
        {
            $msg = _("No data to create the brand!");
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        elseif (! is_array($new))
        {
            $msg = _("The data to create the brand must be an array!");
            if ( $noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        
        // Check if the required keys are set
        $missing_keys = array_diff_key(array_flip(['id', 'brand', 'model', 'max_lines', 'template_list', 'product_id']), $new);
        if (! empty($missing_keys))
        {
            $msg = sprintf(_("Missing keys: %s"), implode(', ', array_keys($missing_keys)));
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        unset($missing_keys);

        // Check if the ID is exists in the database
        if ($this->isExistID($new['id']))
        {
            $msg = sprintf(_("Product ID '%s' already exists!"), $this->id);
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }

        // Set the default values if they are not set
		$new['max_lines']     = $new['max_lines'] 	  ?? 1;
        $new['hidden']        = $new['hidden']    	  ?? false;
		$new['enabled']       = $new['enabled']   	  ?? false;
		$new['template_list'] = $new['template_list'] ?? [];
		$new['template_data'] = $new['template_data'] ?? '';

        // Convert the boolean values to integer
        $new['hidden']		  = $new['hidden']  ? 1 : 0;
		$new['enabled']    	  = $new['enabled'] ? 1 : 0;

		$new['template_list'] = is_array($new['template_list']) ? serialize($new['template_list']) : $new['template_list'];
		$new['template_data'] = is_array($new['template_data']) ? serialize($new['template_data']) : $new['template_data'];


        $return_insert = $this->insertQuery("endpointman_model_list", $new, ['id'], true);
        if ($return_insert === false)
        {
            $msg = _("Insert Query Failed!");
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        
        if (! $this->findBy('id', $new['id']))
        {
            $msg = sprintf(_("Model ID '%s' not created!"), $new['id']);
            if ($noException)
            {
                $this->sendDebug($msg);
                return false;
            }
            throw new \Exception($msg);
        }
        return $return_new_id ? $new['id'] : true;
    }
	
	
    /**
     * Deletes the family from the database.
     */
    public function delete()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->deleteQuery("endpointman_model_list", $this->id);
        if ($result === false)
        {
            return false;
        }
        $this->id      = null;
        $this->destory = true;
    }


	public function getFamilyId(bool $viaThis = false)
    {
        if ($viaThis)
        {
            if (! $this->isFamilySet())
            {
                return false;
            }
            return $this->family->getID();
        }
        else
        {
            if (! $this->isExistID())
            {
                return false;
            }
            $result = $this->querySelect("endpointman_model_list", "product_id", $this->id, 1, true);
            if (empty($result))
            {
                return false;
            }
            return $result;
        }
    }

    public function getFamily()
    {
        return $this->family;
    }
    
    public function setFamily($new = null)
    {
        if(is_numeric($new))
        {
            $family = new ProvisionerFamilyDB($this->parent);
            $family->findBy('id', $new);
        }
        else if (is_string($new))
        {
            $family = new ProvisionerFamilyDB($this->parent);
            $family->findBy('cfg_dir', $new);
        }
        else if ($new instanceof ProvisionerFamilyDB || is_null($new))
        {
            $family = $new;
        }
        else
        {
            return false;
        }

        $this->family = $family;
        return $this->family;
    }

    public function isFamilySet()
    {
        if (empty($this->family))
        {
            return false;
        }
        else if (! $this->family instanceof ProvisionerFamilyDB)
        {
            return false;
        }
        else if ($this->family->isDestory())
        {
            return false;
        }
        return true;
    }





	public function getDirectorys()
	{
		$return_data = [
			'brand'  => null,
			'family' => null,
		];
		if ($this->isExistID())
		{
			if ($this->isFamilySet())
			{
				$return_data['family'] = $this->getFamily()->getDirectory();

				if ($this->getFamily()->isBrandSet())
				{
					$return_data['brand'] = $this->getFamily()->getBrand()->getDirectory();
				}
			}
		}
		return $return_data;
	}























    /**
     * Retrieves the model ID.
     *
     * @return string The model ID or null if the ID is empty.
     */
    public function getId()
    {
        if (empty($this->id))
        {
            return null;
        }
        return $this->id;
    }


    /**
     * Retrieves the model name.
     *
     * @return string The model name.
     */
    public function getModel()
    {
        if (! $this->isExistID())
        {
            return false;
        }
        $result = $this->querySelect("endpointman_model_list", "model", $this->id, 1, true);
        if (empty($result))
        {
            return false;
        }
        return $result;
    }


    /**
     * Retrieves the maximum number of lines.
     *
     * @return int The maximum number of lines or 1 if the number of lines is empty.
     */
    public function getMaxLines()
    {
		if (! $this->isExistID())
		{
			return false;
		}
		$result = $this->querySelect("endpointman_model_list", "max_lines", $this->id, 1, true);
		if (empty($result))
		{
			return false;
		}
		return $result;
    }

	/**
	 * Sets the maximum number of lines.
	 * 
	 * @param int $lines The maximum number of lines.
	 * @return bool True if the maximum number of lines is set, false otherwise.
	 */
	public function setMaxLines(int $lines)
    {
        if (! $this->isExistID())
        {
            return false;
        }
		if (! is_numeric($lines))
		{
			return false;
		}
        return $this->updateQuery("endpointman_product_list", ['max_lines' => $lines], $this->id);
    }



    /**
     * Retrieves the list of templates.
     *
     * @return array The list of templates.
     */
    public function getTemplateList()
    {
		if (! $this->isExistID())
		{
			return false;
		}
		$result = $this->querySelect("endpointman_model_list", "template_list", $this->id, 1, true);
		$result = unserialize($result);

		if (empty($result) || ! is_array($result))
		{
			return [];
		}
		return $result;
    }

	public function setTemplateList(array $list = [])
	{
		if (! $this->isExistID())
		{
			return false;
		}
		else if (! is_array($list))
		{
			return false;
		}
		$list = serialize($list);
		return $this->updateQuery("endpointman_model_list", ['template_list' => $list], $this->id);
	}

	public function getTemplateData(string $template)
	{
		if (! $this->isExistID())
		{
			return false;
		}
		$list = $this->getTemplateList();
		if (empty($list))
		{
			return false;
		}
		if (! in_array($template, $list))
		{
			return false;
		}
		$result = $this->querySelect("endpointman_model_list", "template_data", $this->id, 1, true);
		$result = unserialize($result);
		if (empty($result) || ! is_array($result))
		{
			return [];
		}
		return $result;
	}

	public function setTemplateData(array $data = [])
	{
		if (! $this->isExistID())
		{
			return false;
		}
		else if (! is_array($data))
		{
			return false;
		}
		$data = serialize($data);
		return $this->updateQuery("endpointman_model_list", ['template_data' => $data], $this->id);
	}


	public function getEnabled()
	{
		if (! $this->isExistID())
		{
			return false;
		}
		$result = $this->querySelect("endpointman_model_list", "enabled", $this->id, 1, true);
		if (empty($result))
		{
			return false;
		}
		return $result;
	}

	public function setEnabled(bool $enabled)
	{
		if (! $this->isExistID())
		{
			return false;
		}
		$enabled = $enabled ? 1 : 0;
		return $this->updateQuery("endpointman_model_list", ['enabled' => $enabled], $this->id);
	}

	public function getHidden()
	{
		if (! $this->isExistID())
		{
			return false;
		}
		$result = $this->querySelect("endpointman_model_list", "hidden", $this->id, 1, true);
		if (empty($result))
		{
			return false;
		}
		return $result;
	}

	public function setHidden(bool $hidden)
	{
		if (! $this->isExistID())
		{
			return false;
		}
		$hidden = $hidden ? 1 : 0;
		return $this->updateQuery("endpointman_model_list", ['hidden' => $hidden], $this->id);
	}






    public function exportInfo(){
        $info = [
            'isPartent'     => array(
                'get'  => $this->isParentSet(),
                'type' => gettype($this->parent),
            ),
            'system'        => gettype($this->system),
            'debug'         => $this->debug,
            'id'            => array (
                'get' => $this->getID(),
                'raw' => $this->id,
            ),
			'family'        => array (
				'get' => $this->getFamily(),
				'raw' => $this->family,
			),
			'model'         => $this->getModel(),
			'max_lines'     => $this->getMaxLines(),
			'template_list' => $this->getTemplateList(),
			'enabled'       => $this->getEnabled(),
			'hidden'        => $this->getHidden(),

            'isDestory' => array (
                'get' => $this->isDestory(),
                'raw' => $this->destory,
            ),
        ];
        return $info;
    }
}