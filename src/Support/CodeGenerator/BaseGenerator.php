<?php

namespace Slowlyo\OwlAdmin\Support\CodeGenerator;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class BaseGenerator
{
    protected static array $files = [];

    protected string $primaryKey = '';

    protected string $title = '';

    public static function make(): static
    {
        return new static();
    }

    public function primary($key): static
    {
        $this->primaryKey = $key;

        return $this;
    }

    public function title($title): static
    {
        $this->title = $title;

        return $this;
    }

    public static function fromJson(?string $path)
    {
        if (isset(static::$files[$path])) {
            return static::$files[$path];
        }

        if (!$path || !is_file($path)) {
            return static::$files[$path] = [];
        }

        try {
            return static::$files[$path] = (array)json_decode(app('files')->get($path), true);
        } catch (\Throwable $e) {
        }

        return static::$files[$path] = [];
    }

    public static function guessClassFileName($class): bool|string
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        try {
            if (class_exists($class)) {
                return (new \ReflectionClass($class))->getFileName();
            }
        } catch (\Throwable $e) {
        }
        $class = trim($class, '\\');
        $autoloadFile = base_path('/vendor/autoload.php');
        $loader = require $autoloadFile;
        $map = collect($loader->getPrefixesPsr4())->mapWithKeys(function ($path, $namespace) {
            $namespace = trim($namespace, '\\') . '\\';
            return [$namespace => [$namespace, array_pop($path)]];
        })->sortBy(function ($_, $namespace) {
            return strlen($namespace);
        }, SORT_REGULAR, true);
        $prefix = explode($class, '\\')[0];


        if ($map->isEmpty()) {
            if (Str::startsWith($class, 'App\\')) {
                $values = ['App\\', 'app/'];
            }
        } else {
            $values = $map->filter(function ($_, $k) use ($class) {
                $k1 = str_replace('\\', '/', $k);
                return Str::startsWith($class, $k1) ||  Str::startsWith($class, $k);
            })->first();
        }
        if (empty($values)) {
            $values = [$prefix . '\\', self::slug($prefix) . '/'];
        }

        [$namespace, $path] = $values;

        $class = str_replace('/', '\\', $class);
        return str_replace([$namespace, '\\'], [$path, '/'], $class) . '.php';
    }

    public static function slug(string $name, string $symbol = '-'): array|string
    {
        $text = preg_replace_callback('/([A-Z])/', function ($text) use ($symbol) {
            return $symbol . strtolower($text[1]);
        }, $name);

        return str_replace('_', $symbol, ltrim($text, $symbol));
    }

    protected function replaceClass(&$stub, $name): static
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        $stub = str_replace('{{ ClassName }}', $class, $stub);

        return $this;
    }

    protected function replaceTitle(&$stub): static
    {
        $stub = str_replace('{{ AppTitle }}', $this->title, $stub);

        return $this;
    }

    protected function replaceNamespace(&$stub, $name): static
    {
        $stub = str_replace(
            '{{ Namespace }}',
            $this->getNamespace($name),
            $stub
        );

        return $this;
    }

    public function replaceSpace($stub): array|string
    {
        return str_replace(["\n\n\n", "\n    \n"], ["\n\n", ''], $stub);
    }

    protected function getNamespace($name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }
}
