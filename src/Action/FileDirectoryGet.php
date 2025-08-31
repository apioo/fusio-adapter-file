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
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Http\Exception as StatusCode;

/**
 * FileDirectoryGet
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectoryGet extends FileReaderAbstract
{
    use FileDirectoryTrait;

    public function getName(): string
    {
        return 'File-Directory-Get';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $connection = $this->getConnection($configuration);
        if (!$connection instanceof Filesystem) {
            $connection = $this->getDirectory($configuration);
        }

        $id = $request->get('id');
        if (empty($id)) {
            throw new StatusCode\BadRequestException('No id provided');
        }

        $file = $this->findFileById($connection, $id);
        if (!$file instanceof FileAttributes) {
            throw new StatusCode\NotFoundException('Provided id does not exist');
        }

        return $this->read($connection, $file, $request);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The Filesystem connection which should be used, this is optional in case you provide a directory'));
        $builder->add($elementFactory->newInput('directory', 'Directory', 'text', 'A path to a directory which you want expose, this is optional in case your provide a connection'));
        $builder->add($elementFactory->newInput('delimiter', 'Delimiter', 'text', 'Optional a delimiter for CSV files default is ";"'));
    }

    private function findFileById(Filesystem $connection, string $id): ?FileAttributes
    {
        $files = $this->getFilesInDirectory($connection, null);
        foreach ($files as $file) {
            if ($this->getUuidForFile($file) === $id) {
                return $file;
            }
        }

        return null;
    }
}
