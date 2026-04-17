<?php

namespace Savv\Console\Commands;

class MakeConfig
{
    public function execute(array $args): void
    {
        if (empty($args)) {
            echo "Error: Please provide a config name (e.g., php savv make:config redirections)\n";
            return;
        }

        $name = strtolower($args[0]);
        $path = ROOT_PATH . "/configs/{$name}.php";

        if (file_exists($path)) {
            echo "Error: Config file '{$name}.php' already exists.\n";
            return;
        }

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $stub = "<?php\n\nreturn [\n    // Define your {$name} configuration here\n];\n";

        if (file_put_contents($path, $stub)) {
            echo "Success: Config file created at configs/{$name}.php\n";
        }
    }
}