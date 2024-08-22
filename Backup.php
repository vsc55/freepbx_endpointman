<?php
namespace FreePBX\modules\Endpointman;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase
{
	public function runBackup($id,$transaction)
	{
		/*
		$this->addConfigs([
			'data' => $this->FreePBX->Endpointman->getCategories(),
			'settings' => $this->dumpAdvancedSettings()
		]);
		*/
		$dirs 				= [];
		$epm 			 	= $this->FreePBX->Endpointman;
		$location_endpoint	= $epm->buildPath($epm->PHONE_MODULES_PATH, "endpoint");
		$location_temp		= $epm->buildPath($epm->PHONE_MODULES_PATH, "temp");

		$iterator = new RecursiveDirectoryIterator($location_endpoint, RecursiveDirectoryIterator::SKIP_DOTS);
		foreach (new RecursiveIteratorIterator($iterator) as $file)
		{
			$dirs[] = $file->getPath();
			$this->addFile($file->getBasename(), $file->getPath(), '', "endpoint");
		}

		$iterator = new RecursiveDirectoryIterator($location_temp, RecursiveDirectoryIterator::SKIP_DOTS);
		foreach (new RecursiveIteratorIterator($iterator) as $file)
		{
			$dirs[] = $file->getPath();
			$this->addFile($file->getBasename(), $file->getPath(), '', "temp");
		}
		
		$this->addDirectories(array_unique($dirs));
		$this->addConfigs([
			'settings' => $this->dumpAdvancedSettings()
		]);
	}
}