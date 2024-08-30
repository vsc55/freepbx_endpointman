<?php
namespace FreePBX\modules\Endpointman\Provisioner;

require_once('ProvisionerBase.class.php');

class ProvisionerTemplate extends ProvisionerBase
{
    private $file_template = null;

    private $name = '';
 
    public function __construct(?string $file_template, bool $debug = true)
    {
        parent::__construct($debug);
        $this->file_template = $file_template;
    }


    public function importJSON($jsonData = null, bool $noException = false, bool $importChildrens = true)
    {
        if (empty($jsonData) || !is_array($jsonData))
        {
            if ($this->isParentSet())
            {
                $model = $this->getParent();
                if ($model->isParentSet())
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
                }
                else
                {
                    if ($noException) { return false; }
                    throw new \Exception(_("Parent Family is not set!"));
                }
            }
            else
            {
                if ($noException) { return false; }
                throw new \Exception(_("Parent Model is not set!"));
            }
            

            if ( empty($brand->getDirectory()) || empty($family->getDirectory()) )
            {
                if ($noException) { return false; }
                throw new \Exception(_("Brand or Family directory is empty!"));
            }

            if (empty($this->getFileTemplate()))
            {
                if ($noException) { return false; }
                throw new \Exception(_("File template is empty!"));
            }

            $jsonData = $this->system->buildPath($this->getPathEndPoint(), $brand->getDirectory(), $family->getDirectory(), $model->getFileTemplate());
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
        elseif (empty($jsonData['template_data']['category']) || !is_array($jsonData['template_data']['category']))
        {
            if ($noException) { return false; }
            throw new \Exception(sprintf(_("Invalid JSON data, missing 'category' is empty or is not array [%s]!"), __CLASS__));
        }



        // $data = [];



        $categorys = $jsonData['template_data']['category'] ?? [];
        foreach ($categorys as $category)
        {
            $category_name = $category['name'];
            $subcategorys  = $category['subcategory'] ?? [];

            if (empty($subcategorys) || !is_array($subcategorys) )
            {
                if ($this->debug)
                {
                    dbug(sprintf(_("Skip: Subcategory '%s' in the file <b>'%s'</b> is not array!"), $category['name'], $this->getFileTemplate()));
                }
                continue;
            }

            foreach ($subcategorys as $subcategory)
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
                                        $z = str_replace("\$", "", $item_loop['variable']);
                                        $items_loop[$var_nam][$z] 					= $item_loop;
                                        $items_loop[$var_nam][$z]['description'] 	= str_replace('{$count}', $i, $items_loop[$var_nam][$z]['description']);
                                        $items_loop[$var_nam][$z]['default_value'] 	= $items_loop[$var_nam][$z]['default_value'];
                                        $items_loop[$var_nam][$z]['default_value'] 	= str_replace('{$count}', $i, $items_loop[$var_nam][$z]['default_value']);
                                        $items_loop[$var_nam][$z]['line_loop'] 		= TRUE;
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
                                        if (count($z_tmp) < 2) {
                                            $errors[] = sprintf(_("<b>Skip:</b> Loop Variable <b>'%s'</b> format not valid!"), $item_loop['variable'] ?? '');
                                            continue;
                                        }
                                        $z = $z_tmp[1];
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


    public function setFileTemplate(string $file_template)
    {
        $this->file_template = $file_template;
    }

    public function getFileTemplate()
    {
        if (empty($this->file_template))
        {
            return null;
        }
        return $this->file_template;
    }

    

}