<?php

namespace Bluora\PhpElixir\Modules;

use Bluora\PhpElixir\AbstractModule;
use Bluora\PhpElixir\ElixirConsoleCommand as Elixir;

class CopyModule extends AbstractModule
{
    /**
     * Error understanding path.
     */
    const COPY_ERROR = -1;

    /**
     * Copy all files and folders.
     */
    const COPY_ALL = 1;

    /**
     * Copy only files in the base directory.
     */
    const COPY_BASE = 2;

    /**
     * Copy file.
     */
    const COPY_FILE = 3;

    /**
     * Verify the configuration for this task.
     *
     * @param string $source_path
     * @param string $destination_path
     *
     * @return bool
     */
    public static function verify($source_path, $destination_path)
    {
        list($method, $source_path, $destination_path, $options) = self::checkPath($original_source_path = $source_path, $original_destination_path = $destination_path);

        if (!Elixir::checkPath($source_path, false, true)) {
            return false;
        }

        Elixir::storePath($source_path);

        // Generate the new file paths so we can validate other tasks in the future.
        switch ($method) {
            /*
             * Copying all files.
             * or Copying files from the base of the provided directory.
             */
            case self::COPY_ALL:
            case self::COPY_BASE:
                $method_arguments = ($method == self::COPY_BASE) ? [true, 1] : [];
                $paths = Elixir::scan($source_path, false, ...$method_arguments);
                $paths = Elixir::filterPaths($paths, array_get($options, 'source.filter', ''));

                if (substr($destination_path, -1) != '/') {
                    $destination_path .= '/';
                }

                foreach ($paths as $path) {
                    $destination_file = $destination_path.$path;
                    Elixir::storePath($destination_file);
                }
                break;

            /*
             * Copying a single file.
             */
            case self::COPY_FILE:
                if (substr($destination_path, -1) == '/') {
                    $destination_path = Elixir::checkPath($destination_path, !Elixir::dryRun());
                    $source_basename = basename($source_path);
                    $destination_path .= $source_basename;
                }
                Elixir::storePath($destination_path);
                break;

            /*
             * Copying error. File may not exist.
             */
            case self::COPY_ERROR:
                Elixir::console()->error(sprintf('%s not found.', $original_source_path));

                return false;
                break;
        }

        return true;
    }

    /**
     * Check the path, set the options and clean the path.
     *
     * @param string $path
     *
     * @return array
     */
    public static function checkPath($source_path, $destination_path)
    {
        $options = [
            'source'      => [],
            'destination' => [],
        ];

        $source_options = &$options['source'];
        $destination_options = &$options['destination'];

        list($source_path, $source_options) = Elixir::parseOptions($source_path);
        list($destination_path, $destination_options) = Elixir::parseOptions($destination_path);

        if (($index = stripos($source_path, '*.')) !== false) {
            array_set($source_options, 'filter', substr($source_path, $index + 2));
            $source_path = substr($source_path, 0, $index + 1);
        }

        if (substr($source_path, -2) == '**') {
            return [self::COPY_ALL, substr($source_path, 0, -2),  $destination_path, $options];
        }

        if (substr($source_path, -1) == '*' || substr($source_path, -1) == '/') {
            return [self::COPY_BASE, substr($source_path, 0, -1),  $destination_path, $options];
        }

        if (is_file($source_path)) {
            return [self::COPY_FILE, $source_path, $destination_path, $options];
        }

        return [self::COPY_ERROR, '', '', $options];
    }

    /**
     * Run the task.
     *
     * @param string $source_path
     * @param string $destination_path
     *
     * @return bool
     */
    public function run($source_path, $destination_path)
    {
        Elixir::commandInfo('Executing \'copy\' module...');
        Elixir::console()->line('');
        Elixir::console()->info('   Copying Files From...');
        Elixir::console()->line(sprintf(' - %s', $source_path));
        Elixir::console()->line('');
        Elixir::console()->info('   Saving To...');
        Elixir::console()->line(sprintf(' - %s', $destination_path));
        Elixir::console()->line('');

        return $this->process($source_path, $destination_path);
    }

    /**
     * Process the task.
     *
     * @param string $source_path
     * @param string $destination_path
     *
     * @return bool
     */
    private function process($source_path, $destination_path)
    {
        list($method, $source_path, $destination_path, $options) = self::checkPath($source_path, $destination_path);

        switch ($method) {

            /*
             * Copying all files.
             */
            case self::COPY_ALL:
                $paths = Elixir::scan($source_path, false);
                $paths = Elixir::filterPaths($paths, array_get($options, 'source.filter', ''));

                Elixir::console()->info(sprintf('   Found %s files. Copying...', count($paths)));
                Elixir::console()->line('');

                if (substr($destination_path, -1) != '/') {
                    $destination_path .= '/';
                }

                foreach ($paths as $path) {
                    $new_path = str_replace($source_path, $destination_path, $path);
                    if (array_has($options, 'destination.remove_extension_folder')) {
                        $pathinfo = pathinfo($new_path);
                        $new_path = preg_replace('/\b'.$pathinfo['extension'].'$/', '', $pathinfo['dirname']).$pathinfo['basename'];
                    }
                    $this->copyFile($path, $new_path);
                }

                break;

            /*
             * Copying files from the base of the provided directory.
             */
            case self::COPY_BASE:
                $paths = array_filter(scandir($source_path), function ($path) use ($source_path) {
                    return is_file($source_path.$path);
                });
                $paths = Elixir::filterPaths($paths, array_get($options, 'source.filter', ''));

                Elixir::console()->info(sprintf('   Found %s files. Copying...', count($paths)));
                Elixir::console()->line('');

                if (substr($destination_path, -1) != '/') {
                    $destination_path .= '/';
                }

                foreach ($paths as $path) {
                    if ($path === '.' || $path === '..') {
                        continue;
                    }
                    $source_file = $source_path.$path;
                    $destination_file = $destination_path.$path;
                    $this->copyFile($source_file, $destination_file);
                }
                break;

            /*
             * Copying a single file.
             */
            case self::COPY_FILE:
                if (substr($destination_path, -1) == '/') {
                    $destination_path = Elixir::checkPath($destination_path, !Elixir::dryRun());
                    $source_basename = basename($source_path);
                    $destination_path .= $source_basename;
                }

                $this->copyFile($source_path, $destination_path);
                break;

            /*
             * Error understanding path.
             */
            case self::COPY_ERROR:
                Elixir::console()->error($source_path);

                return false;
                break;
        }

        if (Elixir::verbose()) {
            Elixir::console()->line('');
        }

        return true;
    }

    /**
     * Copy file.
     *
     * @param string $source_path
     * @param string $destination_path
     *
     * @return void
     */
    private function copyFile($source_path, $destination_path)
    {
        if (Elixir::verbose()) {
            Elixir::console()->line(sprintf(' - From: %s', str_replace(base_path(), '', $source_path)));
            Elixir::console()->line(sprintf('   To:   %s', str_replace(base_path(), '', $destination_path)));
            Elixir::console()->line('');
        }

        if (!Elixir::dryRun()) {
            Elixir::makeDir($destination_path);
            copy($source_path, $destination_path);
        }
    }
}
