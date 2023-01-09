<?php

/*
 * Copyright 2005 - 2023 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */

declare(strict_types=1);

namespace Utility;

class EnvironmentFileManager
{
    /** @var array<string, int|float|bool|string> */
    private array $variables = [];

    private ?string $currentEnvironnementFile = null;

    /**
     * @param string $environmentFilePath
     */
    public function __construct(private string $environmentFilePath)
    {
        $this->environmentFilePath = $this->addDirectorySeparatorIfNeeded($this->environmentFilePath);
    }

    /**
     * @param string $environmentFile
     *
     * @throws \Exception
     */
    public function load(string $environmentFile = '.env'): void
    {
        $this->currentEnvironnementFile = $environmentFile;
        $filePath = $this->environmentFilePath . $environmentFile;
        if (! file_exists($filePath)) {
            throw new \Exception(sprintf("The environment file '%s' does not exist", $environmentFile));
        }

        $file = fopen($filePath, 'r');
        if (! $file) {
            throw new \Exception(sprintf('Impossible to open file \'%s\'', $filePath));
        }
        try {
            while (($line = fgets($file, 1024)) !== false) {
                $line = trim($line);
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line);
                    $this->add($key, $value);
                }
            }
        } finally {
            fclose($file);
        }
    }

    /**
     * @param string $key
     * @param int|float|bool|string $value
     */
    public function add(string $key, int|float|bool|string $value): void
    {
        if ($value === 'true' || $value === 'false') {
            $value = (bool) $value;
        } elseif (preg_match('/^-?\d+\.\d+/', (string) $value)) {
            $value = (float) $value;
        } elseif (is_numeric($value)) {
            if (str_starts_with($key, 'IS_') && $value === '1') {
                $value = (bool) $value;
            } else {
                $value = (int) $value;
            }
        }
        $this->variables[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function delete(string $key): void
    {
        unset($this->variables[$key]);
    }

    /**
     * @return array<string, int|float|bool|string>
     */
    public function getAll(): array
    {
        return $this->variables;
    }

    /**
     * @param string $key
     *
     * @return int|float|bool|string|null
     */
    public function get(string $key): int|float|bool|string|null
    {
        return $this->variables[$key] ?? null;
    }

    /**
     * @param string|null $environmentFile
     *
     * @throws \Exception
     */
    public function save(?string $environmentFile = null): void
    {
        $this->checkEnvironmentFileDefined($environmentFile);
        $filePath = $this->environmentFilePath . $this->currentEnvironnementFile;
        $file = fopen($filePath, 'w');
        if (! $file) {
            throw new \Exception(sprintf('Impossible to open file \'%s\'', $filePath));
        }
        try {
            foreach ($this->variables as $key => $value) {
                if (is_bool($value)) {
                    $value = $value ? 1 : 0;
                }
                fwrite($file, sprintf("%s=%s\n", $key, $value));
            }
        } finally {
            fclose($file);
        }

        $variables = var_export($this->variables, true);
        $content = <<<CONTENT
            <?php

            // This file was generated by Centreon

            return {$variables};
            CONTENT;
        file_put_contents($filePath . '.local.php', $content);
    }

    /**
     * @param string|null $environmentFile
     *
     * @throws \Exception
     */
    private function checkEnvironmentFileDefined(?string $environmentFile = null): void
    {
        $environmentFile ??= $this->currentEnvironnementFile;
        if ($environmentFile === null) {
            throw new \Exception('No environment file defined');
        }
    }

    /**
     * Add the directory separator at the end of the path if it does not exist.
     *
     * /dir1/dir2 => /dir1/dir2/
     *
     * @param string $path
     *
     * @return string
     */
    private function addDirectorySeparatorIfNeeded(string $path): string
    {
        if ($path[-1] !== DIRECTORY_SEPARATOR) {
            return $path . DIRECTORY_SEPARATOR;
        }

        return $path;
    }
}
