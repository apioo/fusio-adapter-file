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
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * FileProcessor
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class FileProcessor extends FileReaderAbstract
{
    public function getName(): string
    {
        return 'File-Processor';
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): HttpResponseInterface
    {
        $connection = $this->getConnection($configuration);

        $file = $configuration->get('file');
        if (empty($file)) {
            throw new ConfigurationException('No file configured');
        }

        if (!$connection instanceof Filesystem) {
            $baseDir = dirname($file);
            $file = basename($file);
            $connection = new Filesystem(new LocalFilesystemAdapter($baseDir));
        }

        $file = $this->findFileByName($connection, $file);
        if (!$file instanceof FileAttributes) {
            throw new ConfigurationException('Configured file does not exist');
        }

        return $this->read($connection, $file, $request);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The Filesystem connection which should be used, this is optional in case you provide an absolute path'));
        $builder->add($elementFactory->newInput('file', 'File', 'text', 'A file name in case a connection was provided or an absolute path to a file'));
    }

    private function findFileByName(Filesystem $connection, string $name): ?FileAttributes
    {
        $files = $connection->listContents('.');
        foreach ($files as $file) {
            if (!$file instanceof FileAttributes) {
                continue;
            }

            if ($file->path() === $name) {
                return $file;
            }
        }

        return null;
    }
}
