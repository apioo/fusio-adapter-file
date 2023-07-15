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

namespace Fusio\Adapter\File\Generator;

use Fusio\Adapter\File\Action\FileDirectoryGet;
use Fusio\Adapter\File\Action\FileDirectoryGetAll;
use Fusio\Engine\Factory\Resolver\PhpClass;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\Generator\ProviderInterface;
use Fusio\Engine\Generator\SetupInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\Schema\SchemaBuilder;
use Fusio\Engine\Schema\SchemaName;
use Fusio\Model\Backend\ActionConfig;
use Fusio\Model\Backend\ActionCreate;
use Fusio\Model\Backend\OperationCreate;
use Fusio\Model\Backend\SchemaCreate;

/**
 * FileDirectory
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectory implements ProviderInterface
{
    private const SCHEMA_GET_ALL = 'FileDirectory_GetAll';
    private const ACTION_GET_ALL = 'FileDirectory_GetAll';
    private const ACTION_GET = 'FileDirectory_Get';

    public function getName(): string
    {
        return 'File-Directory';
    }

    public function setup(SetupInterface $setup, ParametersInterface $configuration): void
    {
        $directory = $configuration->get('directory');
        if (!is_dir($directory)) {
            throw new \RuntimeException('Provided directory does not exist');
        }

        $setup->addSchema($this->makeGetAllSchema());

        $setup->addAction($this->makeGetAllAction($directory));
        $setup->addAction($this->makeGetAction($directory));

        $setup->addOperation($this->makeGetAllOperation());
        $setup->addOperation($this->makeGetOperation());
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('directory', 'Directory', 'text', 'A path to a directory which you want expose'));
    }

    private function makeGetAllSchema(): SchemaCreate
    {
        $schema = new SchemaCreate();
        $schema->setName(self::SCHEMA_GET_ALL);
        $schema->setSource(SchemaBuilder::makeCollectionResponse(self::SCHEMA_GET_ALL, \json_decode(\file_get_contents(__DIR__ . '/schema/file.json'))));
        return $schema;
    }

    private function makeGetAllAction(string $directory): ActionCreate
    {
        $action = new ActionCreate();
        $action->setName(self::ACTION_GET_ALL);
        $action->setClass(FileDirectoryGetAll::class);
        $action->setConfig(ActionConfig::fromArray([
            'directory' => $directory,
        ]));
        return $action;
    }

    private function makeGetAction(string $directory): ActionCreate
    {
        $action = new ActionCreate();
        $action->setName(self::ACTION_GET);
        $action->setClass(FileDirectoryGet::class);
        $action->setConfig(ActionConfig::fromArray([
            'directory' => $directory,
        ]));
        return $action;
    }

    private function makeGetAllOperation(): OperationCreate
    {
        $operation = new OperationCreate();
        $operation->setName('getAll');
        $operation->setDescription('Returns a collection of files');
        $operation->setHttpMethod('GET');
        $operation->setHttpPath('/');
        $operation->setHttpCode(200);
        $operation->setParameters(SchemaBuilder::makeCollectionParameters());
        $operation->setOutgoing(self::SCHEMA_GET_ALL);
        $operation->setAction(self::ACTION_GET_ALL);
        return $operation;
    }

    private function makeGetOperation(): OperationCreate
    {
        $operation = new OperationCreate();
        $operation->setName('get');
        $operation->setDescription('Returns a single file');
        $operation->setHttpMethod('GET');
        $operation->setHttpPath('/:id');
        $operation->setHttpCode(200);
        $operation->setOutgoing(SchemaName::PASSTHRU);
        $operation->setAction(self::ACTION_GET);

        return $operation;
    }
}
