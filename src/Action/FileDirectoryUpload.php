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
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use League\Flysystem\Filesystem;
use PSX\Data\Multipart\Body;
use PSX\Data\Multipart\File;
use PSX\Http\Environment\HttpResponse;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Http\Exception as StatusCode;

/**
 * FileDirectoryUpload
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectoryUpload extends FileReaderAbstract
{
    use FileDirectoryTrait;

    public function getName(): string
    {
        return 'File-Directory-Upload';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $connection = $this->getConnection($configuration);
        if (!$connection instanceof Filesystem) {
            throw new ConfigurationException('No connection configured');
        }

        foreach ($this->getUploadedFiles($request->getPayload()) as $name => $resource) {
            $connection->writeStream($name, $resource);
        }

        return new HttpResponse(201, [], [
            'success' => true,
            'message' => 'File successfully uploaded',
        ]);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The Filesystem connection where to store the files'));
    }

    /**
     * @return iterable<resource>
     */
    protected function getUploadedFiles(mixed $body): iterable
    {
        if (!$body instanceof Body) {
            throw new StatusCode\BadRequestException('Request must be an multipart form upload');
        }

        foreach ($body->getAll() as $part) {
            if (!$part instanceof File) {
                continue;
            }

            if ($part->getError() !== UPLOAD_ERR_OK) {
                throw new StatusCode\BadRequestException('There was an error with the file upload');
            }

            $name = $part->getName();
            if (empty($name)) {
                throw new StatusCode\BadRequestException('Provided no file name');
            }

            if (!preg_match('/^[A-Za-z0-9-_.]{3,64}$/', $name)) {
                throw new StatusCode\BadRequestException('Provided file name contains invalid characters');
            }

            $tmpName = $part->getTmpName();
            if (empty($tmpName) || !is_file($tmpName)) {
                throw new StatusCode\BadRequestException('Could not find uploaded file');
            }

            $handle = fopen($tmpName, 'r');
            if (!is_resource($handle)) {
                throw new StatusCode\BadRequestException('Could not read uploaded file');
            }

            yield $name => $handle;
        }
    }
}
