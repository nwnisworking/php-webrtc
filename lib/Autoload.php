<?php
use RecursiveDirectoryIterator as RDI;
use RecursiveIteratorIterator as RII;

final class Autoload{
	private static array $path = [];

	/**
	 * Load files from the provided directory. 
	 * 
	 * @return void
	 */
	public static function load(string $dir, ?string $prepend = null, bool $last = false): void{
		$rii = new RII(new RDI($dir, RDI::SKIP_DOTS));

		foreach($rii as $file){
			$path = $file->getPathName();
			$class = str_replace([$dir, '.php'], '', $path);
			
			if(pathinfo($path, PATHINFO_EXTENSION) !== 'php')
				continue;

			if($last)
				$dir = substr($dir, 0, strrpos($dir, '\\'));

			if(!empty($prepend))
				$class = $prepend.$class;
			else
				$class = substr($class, 1);
			
			self::$path[$class] = $path;
		}
	}

	public static function register(string $class): void{
		$path = self::$path[$class];

		if(!isset($path))
			throw new Exception('File cannot be found in the preloaded path: '.$class);

		include_once $path;
	}

	/**
	 * Filters loaded path and returns the filtered array 
	 * @return array
	 */
  public static function filter(string $value, int $mode = ARRAY_FILTER_USE_KEY): array{
		return array_filter(self::$path, fn($e)=>str_contains($e, $value), $mode);
	}
}