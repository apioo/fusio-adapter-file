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

use Fusio\Engine\ContextInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * FileDirectoryIndex
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectoryGetAll extends FileReaderAbstract
{
    use FileDirectoryTrait;

    public function getName(): string
    {
        return 'File-Directory-GetAll';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $connection = $this->getConnection($configuration);
        if (!$connection instanceof Filesystem) {
            $connection = $this->getDirectory($configuration);
        }

        $startIndex = (int) $request->get('startIndex');
        $count = (int) $request->get('count');

        $itemsPerPage = $count >= 1 && $count <= 64 ? $count : 16;
        $startIndex   = max($startIndex, 0);

        $files = $this->getFilesInDirectory($connection, $request);
        $totalResults = count($files);
        $files = array_slice($files, $startIndex, $itemsPerPage);

        $data = [];
        foreach ($files as $file) {
            if (!$file instanceof FileAttributes) {
                continue;
            }

            try {
                $lastModified = $this->getDateTimeFromTimeStamp($connection->lastModified($file->path()));
            } catch (FilesystemException) {
                $lastModified = null;
            }

            try {
                $contentType = $connection->mimeType($file->path());
            } catch (FilesystemException) {
                $contentType = null;
            }

            $data[] = [
                'id' => $this->getUuidForFile($file),
                'name' => $file->path(),
                'size' => $file->fileSize(),
                'contentType' => $contentType,
                'checksum' => $connection->checksum($file->path()),
                'lastModified' => $lastModified?->toString(),
            ];
        }

        return $this->response->build(200, [], [
            'totalResults' => $totalResults,
            'itemsPerPage' => $itemsPerPage,
            'startIndex' => $startIndex,
            'entry' => $data,
        ]);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The Filesystem connection which should be used, this is optional in case you provide a directory'));
        $builder->add($elementFactory->newInput('directory', 'Directory', 'text', 'A path to a directory which you want expose, this is optional in case your provide a connection'));
    }
}
