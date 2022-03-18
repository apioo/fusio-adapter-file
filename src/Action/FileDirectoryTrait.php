<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Adapter\File\Action;

use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\ParametersInterface;
use Ramsey\Uuid\Uuid;

/**
 * FileActionTrait
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
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
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file[0] === '.') {
                continue;
            }

            $result[] = $file;
        }

        return $result;
    }

    private function getUuidForFile(string $file): string
    {
        return Uuid::uuid5('fusio-adapter-file', $file)->toString();
    }
}
