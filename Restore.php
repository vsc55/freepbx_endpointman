<?php
namespace FreePBX\modules\Endpointman;
use FreePBX\modules\Backup as Base;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		$files = $this->getFiles();
		/*
		foreach ($configs['data'] as $category) {
			$this->FreePBX->Endpointman->addCategoryById($category['id'], $category['category'], $category['type']);
			$this->FreePBX->Endpointman->updateCategoryById($category['id'], $category['type'], $category['random'], $category['application'], $category['format']);
		}
		*/
		$this->importAdvancedSettings($config['settings']);
		foreach ($files as $file) {
			$filename = $file->getPathTo().'/'.$file->getFilename();
			if(file_exists($filename)){
					continue;
			}
			copy($this->tmpdir.'/files/'.$file->getPathTo().'/'.$file->getFilename(), $filename);
		}
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$this->restoreLegacyAdvancedSettings($pdo);

		if(version_compare_freepbx($this->getVersion(),"13","ge")) {
			$this->restoreLegacyDatabase($pdo);
		}
		else{
			
		}

		if(!file_exists($this->tmpdir.'/'.$this->FreePBX->Endpointman->PHONE_MODULES_PATH)) {
			return;
		}

		$endpoint_dir_data = $this->FreePBX->Endpointman->PHONE_MODULES_PATH.'/endpoint';
		$endpoint_dir_temp = $this->FreePBX->Endpointman->PHONE_MODULES_PATH.'/temp';
		shell_exec("rm -rf $endpoint_dir_data 2>&1");
		shell_exec("rm -rf $endpoint_dir_temp 2>&1");

		$finder = new Finder();
		$fileSystem = new Filesystem();
		foreach ($finder->in($this->tmpdir.'/'.$endpoint_dir_data) as $item) {
			if($item->isDir()) {
				$fileSystem->mkdir($endpoint_dir_data.'/'.$item->getRelativePathname());
				continue;
			}
			$fileSystem->copy($item->getPathname(), $endpoint_dir_data.'/'.$item->getRelativePathname(), true);
		}
		
		foreach ($finder->in($this->tmpdir.'/'.$endpoint_dir_temp) as $item) {
			if($item->isDir()) {
				$fileSystem->mkdir($endpoint_dir_temp.'/'.$item->getRelativePathname());
				continue;
			}
			$fileSystem->copy($item->getPathname(), $endpoint_dir_temp.'/'.$item->getRelativePathname(), true);
		}
		
		
	}
}
