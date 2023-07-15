<?php
/*
 * Fusio is an open source API management platform which helps to create innovative API solutions.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright 2015-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * FileDirectoryIndex
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectoryGetAll extends ActionAbstract
{
    use FileDirectoryTrait;

    public function getName(): string
    {
        return 'File-Directory-GetAll';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $directory = $this->getDirectory($configuration);

        $startIndex = (int) $request->get('startIndex');
        $count = (int) $request->get('count');

        $itemsPerPage = $count >= 1 && $count <= 64 ? $count : 16;
        $startIndex   = max($startIndex, 0);

        $files = $this->getFilesInDirectory($directory);
        $files = $this->filter($request, $files);
        $files = $this->sort($request, $files);

        $files = array_slice($files, $startIndex, $itemsPerPage);

        $data = [];
        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            $data[] = [
                'id' => $this->getUuidForFile($file),
                'fileName' => $file,
                'size' => filesize($path),
                'contentType' => mime_content_type($path),
                'sha1' => sha1_file($path),
                'lastModified' => (new \DateTime('@' . filemtime($path)))->format(\DateTimeInterface::RFC3339),
            ];
        }

        return $this->response->build(200, [], [
            'totalResults' => count($files),
            'itemsPerPage' => $itemsPerPage,
            'startIndex'   => $startIndex,
            'entry'        => $data,
        ]);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('directory', 'Directory', 'text', 'A path to a directory which you want expose'));
    }

    private function filter(RequestInterface $request, array $files): array
    {
        $filterOp    = $request->get('filterOp');
        $filterValue = $request->get('filterValue');

        if (!empty($filterOp) && !empty($filterValue)) {
            switch ($filterOp) {
                case 'contains':
                    return array_filter($files, function(string $fileName) use ($filterValue): bool {
                        return str_contains($fileName, $filterValue);
                    });

                case 'equals':
                    return array_filter($files, function(string $fileName) use ($filterValue): bool {
                        return $fileName === $filterValue;
                    });

                case 'startsWith':
                    return array_filter($files, function(string $fileName) use ($filterValue): bool {
                        return str_starts_with($fileName, $filterValue);
                    });
            }
        }

        return $files;
    }

    private function sort(RequestInterface $request, array $files): array
    {
        $sortOrder = $request->get('sortOrder');
        if (!empty($sortOrder) && in_array($sortOrder, ['ASC', 'DESC'])) {
            if ($sortOrder === 'DESC') {
                rsort($files);
            } else {
                sort($files);
            }
        }

        return $files;
    }
}
