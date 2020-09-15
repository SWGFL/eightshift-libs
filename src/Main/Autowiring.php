<?php

/**
 * The file that defines the autowiring process
 *
 * @package EightshiftLibs\Main
 */

declare(strict_types=1);

namespace EightshiftLibs\Main;

/**
 * The file that defines the autowiring process
 */
class Autowiring
{

	/**
	 * Array of psr-4 prefixes. Should be provided by Composer's ClassLoader. $ClassLoader->getPsr4Prefixes().
	 *
	 * @var array
	 */
	protected $psr4Prefixes;

	/**
	 * Project namespace
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Constructs object and inserts prefixes from composer.
	 *
	 * @param array  $psr4Prefixes Composer's ClassLoader psr4Prefixes. $ClassLoader->getPsr4Prefixes().
	 * @param string $namespace    Projects namespace.
	 */
	public function __construct(array $psr4Prefixes, string $namespace )
	{
		$this->psr4Prefixes = $psr4Prefixes;
		$this->namespace    = $namespace;
	}

	/**
	 * Autowiring.
	 *
	 * @return array<array> Array of fully qualified class names.
	 */
	public function buildServiceClasses(): array
	{
		$projectClasses = $this->getClassesInNamespace($this->namespace, $this->psr4Prefixes);

		$dependencyTree = [];
		$filenameIndex  = [];

		// Prepare the filename index.
		$filenameIndex       = $this->buildFilenameIndex($projectClasses);
		$classInterfaceIndex = $this->buildClassInterfaceIndex($projectClasses);

		foreach ($projectClasses as $projectClass) {
			$reflClass = new \ReflectionClass($projectClass);

			// Skip abstract classes, interfaces & traits.
			if ($reflClass->isAbstract() || $reflClass->isInterface() || $reflClass->isTrait()) {
				continue;
			}

			// Skip irrelevant classes.
			if (
				! $this->isServiceClass($reflClass->getInterfaceNames())
				&& (empty($reflClass->getConstructor()) || empty($reflClass->getConstructor()->getParameters()))
			) {
				continue;
			}

			// Build the dependency tree.
			$dependencyTree = array_merge($this->buildDependencyTree($projectClass, $filenameIndex, $classInterfaceIndex), $dependencyTree);
		}

		// Build dependency tree for dependencies. Things that need to be injected but were skipped because
		// they were initially irrelevant.
		foreach ($dependencyTree as $dependencies) {
			foreach ($dependencies as $depClass => $subDeps) {

				// No need to build dependencies for this again if we already have them.
				if (isset($dependencyTree[$depClass])) {
					continue;
				}

				$dependencyTree = array_merge($this->buildDependencyTree($depClass, $filenameIndex, $classInterfaceIndex), $dependencyTree);
			}
		}

		// Convert dependency tree into PHP-DI's definition list.
		$classes = $this->convertDependencyTreeIntoDefinitionList($dependencyTree);

		return $classes;
	}

	/**
	 * Check if provided class is part of a service classes. Check if it contains ServiceInterface.
	 *
	 * @param array $interfaces List of class interfaces.
	 *
	 * @return boolean
	 */
	protected function isServiceClass(array $interfaces = [] ): bool
	{
		foreach ($interfaces as $interface) {
			$items = explode('\\', $interface);
			if (end($items) !== 'ServiceInterface') {
				return true;
			}
		}

		return false;
	}

	/**
	 * Builds the dependency tree for a single class ($relevantClass)
	 *
	 * @param  string $relevantClass       Class we're building dependency tree for.
	 * @param  array  $filenameIndex       Filename index. Maps filenames to class names.
	 * @param  array  $classInterfaceIndex Class interface index. Maps classes to interfaces they implement.
	 * @return array
	 */
	protected function buildDependencyTree(string $relevantClass, array $filenameIndex, array $classInterfaceIndex )
	{
		$dependencyTree = [];
		$reflClass      = new \ReflectionClass($relevantClass);

		// If this class has dependencies, we need to figure those out. Otherwise
		// we just add it to the dependency tree as a class without dependencies.
		if (! empty($reflClass->getConstructor())) {

			// Go through each constructor parameter.
			foreach ($reflClass->getConstructor()->getParameters() as $reflParam) {

				if ($reflParam->getType() === null) {
					continue;
				}

				$classname         = $reflParam->getType()->getName();
				$reflClassForParam = new \ReflectionClass($classname);

				// If the expected type is interface, try guessing based on var name. Otherwise
				// Just inject that class.
				if ($reflClassForParam->isInterface()) {
					$matchedClass = $this->tryToFindMatchingClass($reflParam->getName(), $classname, $filenameIndex, $classInterfaceIndex);

					// If we're unable to find exactly 1 class for whatever reason, just skip it, the user
					// will have to define the dependencies manually.
					if (empty($matchedClass)) {
						continue;
					}

					$dependencyTree[$relevantClass][$matchedClass] = [];
				} else {
					$dependencyTree[$relevantClass][$classname] = [];
				}
			}
		} else {
			$dependencyTree[$relevantClass] = [];
		}

		return $dependencyTree;
	}

	/**
	 * Returns all classes in namespace.
	 *
	 * @param  string $namespace    Name of namespace.
	 * @param  array  $psr4Prefixes Array of psr-4 compliant namespaces and their accompanying folders.
	 * @return array
	 */
	protected function getClassesInNamespace(string $namespace, array $psr4Prefixes ): array
	{
		$classes            = [];
		$namespaceWithSlash = "{$namespace}\\";
		$pathToNamespace    = $psr4Prefixes[$namespaceWithSlash][0] ?? '';

		if (! is_dir($pathToNamespace)) {
			return [];
		}

		$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathToNamespace));
		foreach ($it as $file) {
			if ($file->isDir()) {
				continue;
			}
			if (preg_match('/[A-Z]{1}.*.php/', $file->getFileName())) {
				$classes[] = $this->getNamespaceFromFilepath($file->getPathname(), $namespace, $pathToNamespace);
			}
		}

		return $classes;
	}

	/**
	 * Builds PSR namespace SolplanetVendor\from file's path.
	 *
	 * @param  string $filepath          Path to a file.
	 * @param  string $rootNamespace     Root namespace SolplanetVendor\we're getting classes from.
	 * @param  string $rootNamespacePath Path to root namespace SolplanetVendor\.
	 *
	 * @return string
	 */
	protected function getNamespaceFromFilepath(string $filepath, string $rootNamespace, string $rootNamespacePath ): string
	{
		return $rootNamespace . str_replace(
			[$rootNamespacePath, DIRECTORY_SEPARATOR, '.php'],
			['', '\\', ''],
			$filepath
		);
	}


	/**
	 * Try to uniquely match the $filename.
	 *
	 * @param  string $filename            Filename based on variable name.
	 * @param  string $interfaceName       Interface we're trying to match.
	 * @param  array  $filenameIndex       Filename index. Maps filenames to class names.
	 * @param  array  $classInterfaceIndex Class interface index. Maps classes to interfaces they implement.
	 * @return string
	 *
	 * @throws \Exception If things we're looking for are missing inside filename or classInterface index (which shouldn't happen).
	 */
	protected function tryToFindMatchingClass(string $filename, string $interfaceName, array $filenameIndex, array $classInterfaceIndex ): string
	{

		// If there's no matches in filename index by variable, we need to skip it, this dependency's definition.
		// list need sto be build manually.
		if (! isset($filenameIndex[$filename])) {
			throw new \Exception("File {$filename} not found filenameIndex, aborting");
		}

		// Lets go through each file that's called $filename and check which interfaces that class
		// implements (if any).
		$matches = 0;
		foreach ($filenameIndex[$filename] as $classInFilename) {
			if (! isset($classInterfaceIndex[$classInFilename])) {
				throw new \Exception("Class {$classInFilename} not found in classInterfaceIndex, aborting.");
			}

			// If the current class implements the interface we're looking for, great! We still need to go through all other
			// classes to make sure we don't get more than 1 match.
			if (isset($classInterfaceIndex[$classInFilename][$interfaceName])) {
				$match = $classInFilename;
				$matches++;
			}
		}

		// If we don't have a unique match (i.e. if 2 classes of the same name are implementing the interface we're looking for)
		// then we need to cancel the match because we don't know how to handle that.
		if ($matches !== 1) {
			$match = '';
		}

		return $match;
	}

	/**
	 * Builds the PSR-4 filename index. Maps filenames to class names.
	 *
	 * @param  array $allRelevantClasses PSR-4 Namespace prefixes, can be build this Composer's ClassLoader ($loader->getPsr4Prefixes()).
	 * @return array
	 */
	protected function buildFilenameIndex(array $allRelevantClasses ): array
	{
		$filenameIndex = [];
		foreach ($allRelevantClasses as $relevantClass) {
			$filename = $this->getFilenameFromClass($relevantClass);

			$filenameIndex[$filename][] = $relevantClass;
		}

		return $filenameIndex;
	}

	/**
	 * Builds the PSR-4 class => [$interfaces] index. Maps classes to interfaces they implement.
	 *
	 * @param array $allRelevantClasses PSR-4 Namespace prefixes, can be build this Composer's ClassLoader ($loader->getPsr4Prefixes()).
	 * @return array
	 */
	protected function buildClassInterfaceIndex(array $allRelevantClasses ): array
	{
		$classInterfaceIndex = [];
		foreach ($allRelevantClasses as $relevantClass) {
			$interfaces = array_map(
				function() {
					return true;
				},
				(new \ReflectionClass($relevantClass))->getInterfaces()
			);

			$classInterfaceIndex[$relevantClass] = $interfaces;
		}

		return $classInterfaceIndex;
	}

	/**
	 * Returns filename from fully-qualified class names
	 *
	 * Example: AutowiringTest/Something/Class => class
	 *
	 * @param  string $classname Fully qualified classname.
	 * @return string
	 */
	protected function getFilenameFromClass(string $classname ): string
	{
		return lcfirst(trim(substr($classname, strrpos($classname, '\\') + 1)));
	}

	/**
	 * Takes the dependency tree array and convert's it into PHP-DI's definition list. Recursive.
	 *
	 * @param  array $dependencyTree Dependency tree.
	 * @return array
	 */
	protected function convertDependencyTreeIntoDefinitionList(array $dependencyTree )
	{
		$classes = [];
		foreach ($dependencyTree as $className => $dependencies) {
			if (empty($dependencies)) {
				$classes[] = $className;
			} else {
				$classes[$className] = $this->convertDependencyTreeIntoDefinitionList($dependencies);
			}
		}

		return $classes;
	}
}