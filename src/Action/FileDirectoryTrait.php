<?php
/*
 * Fusio - Self-Hosted API Management for Builders.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright (c) Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Fusio\Adapter\File\Action;

use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\ParametersInterface;
use Ramsey\Uuid\Uuid;

/**
 * FileActionTrait
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
trait FileDirectoryTrait
{
    private function getDirectory(ParametersInterface $configuration): string
    {
        $directory = $configuration->get('directory');
        if (!is_dir($directory)) {
            throw new ConfigurationException('Configured directory does not exist');
        }

        return $directory;
    }

    private function getFilesInDirectory(string $directory): array
    {
        $result = [];
        $files = (array) scandir($directory);
        foreach ($files as $file) {
            if ($file === false || $file[0] === '.') {
                continue;
            }

            if (!is_file($directory . '/' . $file)) {
                continue;
            }

            $result[] = $file;
        }

        return $result;
    }

    private function getUuidForFile(string $file): string
    {
        return Uuid::uuid3('8a7f57d1-c7c7-4d96-8662-6da352b2db0b', $file)->toString();
    }
}
