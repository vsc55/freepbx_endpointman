<?php
namespace FreePBX\modules\Endpointman\Provisioner;

require_once(__DIR__.'/../epm_system.class.php');

class ProvisionerModel
{
    private $id        = null;
    private $brand_id  = null;
    private $family_id = null;
    private $model     = '';
    private $lines     = 0;
    private $template  = [];

    public function __construct($id, $model, $lines, $template, $brand_id, $family_id)
    {
        $this->id        = $id;
        $this->brand_id  = $brand_id;
        $this->family_id = $family_id;
        $this->model     = $model;
        $this->lines     = $lines;
        $this->template  = $template;
    }
    
    public function getId()
    {
        return $this->id;
    }

    public function getModelId()
    {
        return sprintf("%s%s%s", $this->brand_id, $this->family_id, $this->id);
    }

    public function getBrandId()
    {
        return $this->brand_id;
    }

    public function getFamilyId()
    {
        return $this->family_id;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getMaxLines()
    {
        return $this->lines;
    }

    public function getTemplateList()
    {
        return $this->template;
    }
}