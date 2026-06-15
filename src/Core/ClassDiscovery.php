<?php declare(strict_types = 1);

namespace TheSaiged\Core;

use RuntimeException;

final class ClassDiscovery {

    /**
     * Returns class names found in $directory that exist in $namespace and
     * extend (or implement) $baseClass. Filenames must match class names (PSR-4).
     *
     * @template T of object
     * @param  class-string<T>  $baseClass
     * @return list<class-string<T>>
     */
    static function inDirectory (string $directory, string $namespace, string $baseClass): array {
        $files = glob("$directory/*.php");
        if ($files === false)
            throw new RuntimeException("Cannot list directory: $directory");

        $classes = [];
        foreach ($files as $file) {
            $class = $namespace . '\\' . basename($file, '.php');
            if (class_exists($class) && is_subclass_of($class, $baseClass))
                $classes[] = $class;
        }
        return $classes;
    }

    /**
     * Scans direct subdirectories of $directory and returns every class found
     * inside any of them that exists and is a subclass of $baseClass.
     * Each subdirectory may contain multiple PHP files (helpers, value objects);
     * only those whose class implements $baseClass are returned.
     *
     * @template T of object
     * @param  class-string<T>  $baseClass
     * @return list<class-string<T>>
     */
    static function inSubdirectories (string $directory, string $namespace, string $baseClass): array {
        $dirs = glob("$directory/*", GLOB_ONLYDIR);
        if ($dirs === false)
            throw new RuntimeException("Cannot list subdirectories of: $directory");

        $classes = [];
        foreach ($dirs as $subdir) {
            $folder = basename($subdir);
            $files  = glob("$subdir/*.php");
            if ($files === false)
                continue;
            foreach ($files as $file) {
                $class = "$namespace\\$folder\\" . basename($file, '.php');
                if (class_exists($class) && is_subclass_of($class, $baseClass))
                    $classes[] = $class;
            }
        }
        return $classes;
    }

}
