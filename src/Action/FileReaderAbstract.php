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
 * @license http://www.apache.org/licenses/LICENSE-2.0
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
