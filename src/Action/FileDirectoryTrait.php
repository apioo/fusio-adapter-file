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

use DateTimeImmutable;
use Exception;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;
use PSX\DateTime\LocalDateTime;
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
    protected function getDirectory(ParametersInterface $configuration): Filesystem
    {
        $directory = $configuration->get('directory');
        if (empty($directory)) {
            throw new ConfigurationException('No directory configured');
        }

        return new Filesystem(new LocalFilesystemAdapter($directory));
    }

    private function getFilesInDirectory(Filesystem $connection, ?RequestInterface $request): array
    {
        $result = $connection->listContents('.');
        $result = $result->filter(static function ($object) use ($request) {
            if (!$object instanceof FileAttributes) {
                return false;
            }

            $filterOp = $request?->get('filterOp');
            $filterValue = $request?->get('filterValue');
            if (is_string($filterOp) && is_string($filterValue)) {
                switch ($filterOp) {
                    case 'contains':
                        return str_contains($object->path(), $filterValue);

                    case 'equals':
                        return $object->path() === $filterValue;

                    case 'startsWith':
                        return str_starts_with($object->path(), $filterValue);
                }
            }

            return true;
        });

        $files = iterator_to_array($result);

        $sortOrder = $request?->get('sortOrder');
        if ($sortOrder === 'DESC') {
            usort($files, static function (StorageAttributes $a, StorageAttributes $b) {
                return strcasecmp($b->path(), $a->path());
            });
        } else {
            usort($files, static function (StorageAttributes $a, StorageAttributes $b) {
                return strcasecmp($a->path(), $b->path());
            });
        }

        return $files;
    }

    private function getUuidForFile(FileAttributes $file): string
    {
        return Uuid::uuid3('8a7f57d1-c7c7-4d96-8662-6da352b2db0b', $file->path())->toString();
    }

    private function getDateTimeFromTimeStamp(int $timeStamp): ?LocalDateTime
    {
        try {
            return LocalDateTime::from(new DateTimeImmutable('@' . $timeStamp));
        } catch (Exception) {
            return null;
        }
    }
}
