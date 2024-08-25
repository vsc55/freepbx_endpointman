<?php
namespace FreePBX\modules\Endpointman;
use FreePBX\modules\Backup as Base;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;
class Restore Extends Base\RestoreBase
{
	public function runRestore()
	{
		$epm 	 = $this->FreePBX->Endpointman;
		$configs = $this->getConfigs();
		$files 	 = $this->getFiles();
		/*
		foreach ($configs['data'] as $category) {
			$this->FreePBX->Endpointman->addCategoryById($category['id'], $category['category'], $category['type']);
			$this->FreePBX->Endpointman->updateCategoryById($category['id'], $category['type'], $category['random'], $category['application'], $category['format']);
		}
		*/
		
		$this->importAdvancedSettings($configs['settings']);
		foreach ($files as $file)
		{
			$path_src  = $epm->system->buildPath($this->tmpdir, 'files', $file->getPathTo(), $file->getFilename());
			$path_dest = $epm->system->buildPath($file->getPathTo(), $file->getFilename());
			if(file_exists($path_dest))
			{
				continue;
			}
			copy($path_src, $path_dest);
		}
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables)
	{
		$epm = $this->FreePBX->Endpointman;

		$this->restoreLegacyAdvancedSettings($pdo);

		if(version_compare_freepbx($this->getVersion(),"13","ge"))
		{
			$this->restoreLegacyDatabase($pdo);
		}
		else
		{
			
		}

		$path_temp_files = $epm->system->buildPath($this->tmpdir, $epm->PHONE_MODULES_PATH);

		if(!file_exists($path_temp_files))
		{
			return;
		}

		$endpoint_dir_data = $epm->system->buildPath($epm->PHONE_MODULES_PATH, 'endpoint');
		$endpoint_dir_temp = $epm->system->buildPath($epm->TEMP_PATH);

		$this->deleteDirectory($endpoint_dir_data);
		$this->deleteDirectory($endpoint_dir_temp);

		// shell_exec("rm -rf $endpoint_dir_data 2>&1");
		// shell_exec("rm -rf $endpoint_dir_temp 2>&1");


		$src_endpoint_dir_data = $epm->system->buildPath($this->tmpdir, $endpoint_dir_data);
		$src_endpoint_dir_temp = $epm->system->buildPath($this->tmpdir, $endpoint_dir_temp);

		$finder 	= new Finder();
		$fileSystem = new Filesystem();
		foreach ($finder->in($src_endpoint_dir_data) as $item)
		{
			$path_item = $epm->system->buildPath($endpoint_dir_data, $item->getRelativePathname());
			if($item->isDir())
			{
				$fileSystem->mkdir($path_item);
				continue;
			}
			$fileSystem->copy($item->getPathname(), $path_item, true);
		}
		
		foreach ($finder->in($src_endpoint_dir_temp) as $item)
		{
			$path_item = $epm->system->buildPath($endpoint_dir_temp, $item->getRelativePathname());

			if($item->isDir())
			{
				$fileSystem->mkdir($path_item);
				continue;
			}
			$fileSystem->copy($item->getPathname(), $path_item, true);
		}
	}

	private function deleteDirectory($dir)
	{
		if (!file_exists($dir)) {
			return true;
		}
	
		if (!is_dir($dir))
		{
			return unlink($dir);
		}
	
		foreach (scandir($dir) as $item)
		{
			if ($item == '.' || $item == '..')
			{
				continue;
			}
			if (! $this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item))
			{
				return false;
			}
		}
		return rmdir($dir);
	}
}
