<?php
// Logo embutido na página (data URI): não depende de onde a pasta assets/ está no servidor.
if (!function_exists('logo_src')) {
    function logo_src(): string
    {
        static $cache = null;
        if ($cache !== null) return $cache;
        foreach (['logo-visual.svg' => 'image/svg+xml', 'logo-visual.png' => 'image/png'] as $file => $mime) {
            $path = __DIR__ . '/' . $file;
            if (is_file($path)) {
                return $cache = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
            }
        }
        return $cache = 'assets/logo-visual.svg'; // último recurso
    }
}
