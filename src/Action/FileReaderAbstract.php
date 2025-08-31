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

use DateTimeInterface;
use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\Request\HttpRequestContext;
use Fusio\Engine\RequestInterface;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Http\Writer\Resource;

/**
 * FileReaderAbstract
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
abstract class FileReaderAbstract extends ActionAbstract
{
    protected function read(Filesystem $connection, FileAttributes $file, RequestInterface $request): HttpResponseInterface
    {
        $headers = [];

        $checksum = $connection->checksum($file->path());
        if (!empty($checksum)) {
            $headers['ETag'] = '"' . $checksum . '"';
        }

        try {
            $lastModified = $connection->lastModified($file->path());
            $headers['Last-Modified'] = date(DateTimeInterface::RFC3339, $lastModified);
        } catch (FilesystemException) {
        }

        $requestContext = $request->getContext();
        if ($requestContext instanceof HttpRequestContext) {
            $match = $requestContext->getRequest()->getHeader('If-None-Match');
            if (!empty($match)) {
                $match = trim($match, '"');
                if ($checksum === $match) {
                    return $this->response->build(304, $headers, '');
                }
            }

            $since = $requestContext->getRequest()->getHeader('If-Modified-Since');
            if (!empty($since)) {
                if (isset($lastModified) && $lastModified < strtotime($since)) {
                    return $this->response->build(304, $headers, '');
                }
            }
        }

        $resource = $connection->readStream($file->path());

        try {
            $contentType = $connection->mimeType($file->path());
        } catch (FilesystemException) {
            $contentType = 'application/octet-stream';
        }

        return $this->response->build(200, $headers, new Resource($resource, $contentType));
    }

    protected function getConnection(ParametersInterface $configuration): ?Filesystem
    {
        $connectionName = $configuration->get('connection');
        if (empty($connectionName)) {
            return null;
        }

        $connection = $this->connector->getConnection($connectionName);
        if (!$connection instanceof Filesystem) {
            return null;
        }

        return $connection;
    }
}
