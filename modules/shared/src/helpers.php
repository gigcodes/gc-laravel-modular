<?php

if (! function_exists('module_path')) {
    /**
     * Get the path to a module directory or file.
     *
     * @param string      $module The module name (e.g., 'user', 'company', 'finance')
     * @param string|null $path   Optional path within the module
     */
    function module_path(string $module, ?string $path = null): string
    {
        $basePath = base_path("modules/{$module}");

        if (is_null($path)) {
            return $basePath;
        }

        return $basePath.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    }
}

if (! function_exists('module_asset')) {
    /**
     * Generate an asset path for a module.
     *
     * @param string $module The module name
     * @param string $path   Asset path within the module
     */
    function module_asset(string $module, string $path): string
    {
        return asset("modules/{$module}/{$path}");
    }
}
