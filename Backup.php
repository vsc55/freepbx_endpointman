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
		$dirs = [];
		$varlibdir = $this->FreePBX->Endpointman->PHONE_MODULES_PATH;
		$iterator = new RecursiveDirectoryIterator($varlibdir.'endpoint', RecursiveDirectoryIterator::SKIP_DOTS);
		foreach (new RecursiveIteratorIterator($iterator) as $file)
		{
			$dirs[] = $file->getPath();
			$this->addFile($file->getBasename(), $file->getPath(), '', "endpoint");
		}
		$iterator = new RecursiveDirectoryIterator($varlibdir.'temp', RecursiveDirectoryIterator::SKIP_DOTS);
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