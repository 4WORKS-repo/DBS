<?php
/**
 * Script to fix esc_js_e() function calls in plugin files.
 * Run this script on the server to fix the fatal error.
 */

// Get all PHP files in the plugin directory
$plugin_dir = __DIR__;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($plugin_dir)
);

$fixed_files = 0;

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $original_content = $content;
        
        // Replace esc_js_e( 'text', 'domain' ) with esc_js( __( 'text', 'domain' ) )
        $content = preg_replace(
            '/esc_js_e\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            'esc_js( __( \'$1\', \'$2\' ) )',
            $content
        );
        
        // Replace esc_js_e( 'text' ) with esc_js( __( 'text', 'distance-shipping' ) )
        $content = preg_replace(
            '/esc_js_e\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            'esc_js( __( \'$1\', \'distance-shipping\' ) )',
            $content
        );
        
        if ($content !== $original_content) {
            file_put_contents($file->getPathname(), $content);
            $relative_path = str_replace($plugin_dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            echo "Fixed esc_js_e() calls in file: " . $relative_path . "\n";
            $fixed_files++;
        }
    }
}

if ($fixed_files > 0) {
    echo "\nFixed $fixed_files file(s). The plugin should now work correctly.\n";
} else {
    echo "No esc_js_e() function calls found to fix.\n";
} 