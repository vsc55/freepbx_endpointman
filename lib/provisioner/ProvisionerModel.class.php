<?php
namespace FreePBX\modules\Endpointman\Provisioner;

// require_once(__DIR__.'/../epm_system.class.php');
require_once('ProvisionerBase.class.php');

class ProvisionerModel extends ProvisionerBase
{
    private $id            = null;
    private $model         = '';
    private $lines         = 1;
    private $template      = [];

    private $family_parent = null;

    

    public function __construct(int $id = null, string $model = "", int $lines = 1, array $template = [], bool $debug = true)
    {
        parent::__construct($debug);

        $this->id        = $id;
        $this->model     = $model;
        $this->lines     = $lines;
        $this->template  = $template;
    }
    


    public function importTemplates(int $maxlines = 12, ?array $errors = array(), bool $noException = false)
    {
		$data   = array();

		$addError = function($msg) use (&$errors)
		{
			$errors[] = $msg;
			if($this->debug)
			{
				dbug($msg);
			}
		};

		if ($this->isParentSet())
		{
			$family = $this->getParent();
			if ($family->isParentSet())
			{
				$brand = $family->getParent();
			}
			else
			{		
				if ($noException) { return false; }
				throw new \Exception(_("Parent Brand is not set!"));
			}
			if ( empty($brand->getDirectory()) || empty($family->getDirectory()) )
			{
				if ($noException) { return false; }
				throw new \Exception(_("Brand or Family directory is empty!"));
			}
		}
		else
		{
			if ($noException) { return false; }
			throw new \Exception(_("Parent Family is not set!"));
		}

        foreach ($this->getTemplateList() as $key => &$value)
        {
            
			$full_path = $this->system->buildPath($this->getPathEndPoint(), $brand->getDirectory(), $family->getDirectory(), $key);
			if (!file_exists($full_path))
			{
				$addError(sprintf(_("❌ File '%s' not found [%s]!"), $full_path, __CLASS__));
				continue;
			}

			try
			{
				$jsonData = $this->system->file2json($full_path);
			}
			catch (\Exception $e)
			{
				$addError(sprintf(_("❌ %s [%s]!"), $e->getMessage(), __CLASS__));
				continue;
			}
			
			if (!is_array($jsonData))
			{
				$addError(sprintf(_("❌ Invalid JSON data [%s]!"), __CLASS__));
				continue;
			}
			elseif (empty($jsonData['template_data']['category']) || !is_array($jsonData['template_data']['category']))
			{
				$addError(sprintf(_("❌ Invalid JSON data, missing 'category' is empty or is not array [%s]!"), __CLASS__));
				continue;
			}
	
			$categorys = $jsonData['template_data']['category'] ?? array();
			foreach ($categorys as $category)
			{
				$category_name = $category['name'];
				$subcategorys = $category['subcategory'] ?? array();

				if (empty($subcategorys) || !is_array($subcategorys) )
				{
					$addError(sprintf(_("❌ERROR: Subcategory '%s' in the file '%s' is empty or is not array!"), $category['name'], $full_path));
					continue;
				}
				foreach ($category['subcategory'] as $subcategory)
				{
					$subcategory_name = $subcategory['name'] ?? 'Settings';
					$items_fin 		  = array();
					$items_loop 	  = array();
					$break_count 	  = 0;
					foreach ($subcategory['item'] as $item)
					{
						switch ($item['type']) 
						{
							case 'loop_line_options':
								for ($i = 1; $i <= $maxlines; $i++) 
								{
									$var_nam = "lineloop|line_" . $i;
									foreach ($item['data']['item'] as $item_loop)
									{
										if ($item_loop['type'] != 'break')
										{
											$z 											= str_replace("\$", "", $item_loop['variable']);
											$items_loop[$var_nam][$z] 					= $item_loop;
											$items_loop[$var_nam][$z]['description'] 	= str_replace('{$count}', $i, $items_loop[$var_nam][$z]['description']);
											$items_loop[$var_nam][$z]['default_value'] 	= $items_loop[$var_nam][$z]['default_value'];
											$items_loop[$var_nam][$z]['default_value']	= str_replace('{$count}', $i, $items_loop[$var_nam][$z]['default_value']);
											$items_loop[$var_nam][$z]['line_loop']		= TRUE;
											$items_loop[$var_nam][$z]['line_count'] 	= $i;
										}
										elseif ($item_loop['type'] == 'break')
										{
											$items_loop[$var_nam]['break_' . $break_count]['type'] = 'break';
											$break_count++;
										}
									}
								}
								$items_fin = array_merge($items_fin, $items_loop);
								break;

							case 'loop':
								for ($i = $item['loop_start']; $i <= $item['loop_end']; $i++)
								{
									$name 	 = explode("_", $item['data']['item'][0]['variable']);
									$var_nam = "loop|" . str_replace("\$", "", $name[0]) . "_" . $i;
									foreach ($item['data']['item'] as $item_loop)
									{
										if ($item_loop['type'] != 'break')
										{
											$z_tmp = explode("_", $item_loop['variable'] ?? '');
											if (count($z_tmp) < 2)
											{
												$addError(sprintf(_("❌ Skip: Loop Variable '%s' format not valid!"), $item_loop['variable'] ?? ''));
												continue;
											}
											$z 											= $z_tmp[1];
											$items_loop[$var_nam][$z] 					= $item_loop;
											$items_loop[$var_nam][$z]['description'] 	= str_replace('{$count}', $i, $items_loop[$var_nam][$z]['description']);
											$items_loop[$var_nam][$z]['variable'] 		= str_replace('_', '_' . $i . '_', $items_loop[$var_nam][$z]['variable']);
											$items_loop[$var_nam][$z]['default_value'] 	= isset($items_loop[$var_nam][$z]['default_value']) ? $items_loop[$var_nam][$z]['default_value'] : '';
											$items_loop[$var_nam][$z]['loop'] 			= TRUE;
											$items_loop[$var_nam][$z]['loop_count'] 	= $i;
										}
										elseif ($item_loop['type'] == 'break')
										{
											$items_loop[$var_nam]['break_' . $break_count]['type'] = 'break';
											$break_count++;
										}
									}
								}
								$items_fin = array_merge($items_fin, $items_loop);
								break;

							case 'break':
								$items_fin['break|' . $break_count]['type'] = 'break';
								$break_count++;
								break;

							default:
								$var_nam = "option|" . str_replace("\$", "", (isset($item['variable'])? $item['variable'] : ""));
								$items_fin[$var_nam] = $item;
								break;
						}
					}
					if (isset($data['data'][$category_name][$subcategory_name]))
					{
						$old_sc 										 = $data['data'][$category_name][$subcategory_name];
						$sub_cat_data[$category_name][$subcategory_name] = array();
						$sub_cat_data[$category_name][$subcategory_name] = array_merge($old_sc, $items_fin);
					}
					else
					{
						$sub_cat_data[$category_name][$subcategory_name] = $items_fin;
					}
				}
				if (isset($data['data'][$category_name]))
				{
					$old_c 						  = $data['data'][$category_name];
					$new_c 						  = $sub_cat_data[$category_name];
					$sub_cat_data[$category_name] = array();
					$data['data'][$category_name] = array_merge($old_c, $new_c);
				}
				else
				{
					$data['data'][$category_name] = $sub_cat_data[$category_name];
				}
			}
        }
		return $data;
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
     * Retrieves the model ID.
     *
     * @return string The model ID in the format: [brand_id][family_id][model_id] or null if any of the IDs is empty.
     */
    public function getModelId()
    {
        if (empty($this->getBrandId()) || empty($this->getFamilyId()) || empty($this->getID()))
        {
            return null;
        }
        return sprintf("%s%s%s", $this->getBrandId(), $this->getFamilyId(), $this->getID());
    }

    /**
     * Retrieves the model name.
     *
     * @return string The model name.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Retrieves the maximum number of lines.
     *
     * @return int The maximum number of lines or 1 if the number of lines is empty.
     */
    public function getMaxLines()
    {
        if (empty($this->lines))
        {
            return 1;
        }
        return $this->lines;
    }

    /**
     * Retrieves the list of templates.
     *
     * @return array The list of templates.
     */
    public function getTemplateList(bool $serialize = false, bool $serialize_json = false)
    {
        if ($serialize)
        {
            if ($serialize_json)
            {
                return json_encode($this->getTemplateList());
            }
            return serialize($this->getTemplateList());
        }
        if (empty($this->template) || !is_array($this->template))
        {
            return [];
        }
        return $this->template;
    }


    
    /**
      * Retrieves the family ID of the parent family.
      *
      * @return string The family ID or null if the family ID is empty or the parent family is not set.
      */
    public function getFamilyId()
    {
        if (! $this->isParentSet())
        {
            return null;    
        }
        return $this->getParent()->getFamilyId();
    }
    
    /**
     * Retrieves the brand ID of the parent family
     *
     * @return string The brand ID or null if the brand ID is empty or the parent family is not set.
     */
    public function getBrandId()
    {
        if (! $this->isParentSet())
        {
            return null;    
        }
        return $this->getParent()->getBrandId();
    }

















 
    
    public function generateJSON()
    {
        $json = [
            'id'       => $this->getId()           ?? '',
			'model_id' => $this->getModelId()      ?? '',
            'model'    => $this->getModel()        ?? '',
            'lines'    => $this->getMaxLines()     ?? '',
            'template' => $this->getTemplateList() ?? array()
        ];
        return $json;
    }

}