<?php

namespace Savv\Console\Commands;

class MakeController
{
    public function execute(array $args): void
    {
        if (empty($args)) {
            echo "Error: Please provide a controller name (e.g., php savv make:controller ContactController)\n";
            return;
        }

        $name = $args[0];
        // Ensure name ends with Controller for consistency
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $path = ROOT_PATH . "/app/Controllers/{$name}.php";

        if (file_exists($path)) {
            echo "Error: Controller '{$name}' already exists.\n";
            return;
        }

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $stub = $this->getStub($name);
        
        if (file_put_contents($path, $stub)) {
            echo "Success: Controller created at app/Controllers/{$name}.php\n";
        }
    }

    protected function getStub(string $name): string
    {
        return "<?php\n\nnamespace App\Controllers;\n\nuse Savv\Utils\Request;\n\nclass {$name}\n{\n    public function index()\n    {\n        return response()->view('index');\n    }\n}\n";
    }
}