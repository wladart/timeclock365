<?php

namespace App\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
	private string $targetDirectory;

	public function __construct(string $targetDirectory)
	{
		$this->targetDirectory = $targetDirectory;
	}

	public function upload(UploadedFile $file): string
	{
		$filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
		$filename = mb_strtolower(preg_replace('/[^A-Za-z0-9_]/u', '_', $filename));
		$newFilename = $filename . '-' . uniqid() . '.' . $file->guessExtension();
		$subFolder = substr(md5($newFilename), 0, 3);
		$filePath = '';

		try
		{
			$file->move(
				$this->getTargetDirectory() . '/' . $subFolder,
				$newFilename
			);

			$filePath = $subFolder . '/' . $newFilename;
		}
		catch (FileException $e)
		{
		}

		return $filePath;
	}

	public function delete(string $filePath): void
	{
		try
		{
			$filesystem = new Filesystem();
			$filesystem->remove($this->getTargetDirectory() . '/' . $filePath);

			[ $subFolder, ] = explode('/', $filePath);
			$subFolder = $this->getTargetDirectory() . '/' . $subFolder;

			$scanResult = scandir($subFolder);
			$scanResult = array_filter($scanResult, function ($val) {
				return !in_array($val, [ '.', '..'], true);
			});
			if (empty($scanResult))
			{
				$filesystem->remove($subFolder);
			}
		}
		catch (\Exception $e)
		{
		}
	}

	private function getTargetDirectory(): string
	{
		return $this->targetDirectory;
	}
}
