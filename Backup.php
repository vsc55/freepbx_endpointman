<?php
namespace FreePBX\modules\Endpointman;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase
{
	public function runBackup($id,$transaction)
	{
		$dirs 				= [];
		$epm 			 	= $this->FreePBX->Endpointman;
		$location_endpoint	= $epm->system->buildPath($epm->PHONE_MODULES_PATH);

		$iterator = new RecursiveDirectoryIterator($location_endpoint, RecursiveDirectoryIterator::SKIP_DOTS);
		foreach (new RecursiveIteratorIterator($iterator) as $file)
		{
			$dirs[] = $file->getPath();
			$this->addFile($file->getBasename(), $file->getPath(), '', "endpoint");
		}
		
		$this->addDirectories(array_unique($dirs)); 
		$this->addConfigs([
			'data' => $this->dumpAll(),
		]);
	}
}