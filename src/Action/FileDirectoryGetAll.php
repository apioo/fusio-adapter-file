<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
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
 * @license http://www.gnu.org/licenses/agpl-3.0
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

        $startIndex   = (int) $request->get('startIndex');
        $itemsPerPage = 16;
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
