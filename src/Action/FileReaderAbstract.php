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

use Fusio\Adapter\File\Csv;
use Fusio\Engine\ActionAbstract;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\Request\HttpRequestContext;
use Fusio\Engine\RequestInterface;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Http\Writer;
use Symfony\Component\Yaml\Yaml;

/**
 * FileReaderAbstract
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
abstract class FileReaderAbstract extends ActionAbstract
{
    public function read(string $file, RequestInterface $request): HttpResponseInterface
    {
        if (!is_file($file)) {
            throw new ConfigurationException('Configured file does not exist');
        }

        $sha1  = sha1_file($file);
        $mtime = filemtime($file);

        $headers = [
            'Last-Modified' => date(\DateTimeInterface::RFC3339, $mtime),
            'ETag' => '"' . $sha1 . '"',
        ];

        $requestContext = $request->getContext();
        if ($requestContext instanceof HttpRequestContext) {
            $match = $requestContext->getRequest()->getHeader('If-None-Match');
            if (!empty($match)) {
                $match = trim($match, '"');
                if ($sha1 == $match) {
                    return $this->response->build(304, $headers, '');
                }
            }

            $since = $requestContext->getRequest()->getHeader('If-Modified-Since');
            if (!empty($since)) {
                if ($mtime < strtotime($since)) {
                    return $this->response->build(304, $headers, '');
                }
            }
        }

        $data = match (pathinfo($file, PATHINFO_EXTENSION)) {
            'json' => $this->wrap(json_decode(file_get_contents($file)), $file),
            'yml', 'yaml' => $this->wrap(Yaml::parse(file_get_contents($file)), $file),
            'csv' => $this->wrap(Csv::parseFile($file), $file),
            default => new Writer\File($file),
        };

        return $this->response->build(200, $headers, $data);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('file', 'File', 'text', 'A path to a file'));
    }

    private function wrap(mixed $value, string $file): object
    {
        return (object) [
            'fileName' => pathinfo($file, PATHINFO_BASENAME),
            'content' => $value
        ];
    }
}
