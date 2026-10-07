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

namespace Fusio\Adapter\File\Generator;

use Fusio\Adapter\File\Action\FileDirectoryGet;
use Fusio\Adapter\File\Action\FileDirectoryGetAll;
use Fusio\Engine\Exception\ConfigurationException;
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
use PSX\Json\Parser;

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
        $config = $this->buildConfig($configuration);

        $setup->addSchema($this->makeGetAllSchema());

        $setup->addAction($this->makeGetAllAction($config));
        $setup->addAction($this->makeGetAction($config));

        $setup->addOperation($this->makeGetAllOperation());
        $setup->addOperation($this->makeGetOperation());
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newConnection('connection', 'Connection', 'The Filesystem connection which should be used'));
        $builder->add($elementFactory->newInput('directory', 'Directory', 'text', 'A path to a directory which you want expose'));
    }

    private function buildConfig(ParametersInterface $configuration): ActionConfig
    {
        $connection = $configuration->get('connection');
        if (!empty($connection)) {
            return ActionConfig::fromArray([
                'connection' => $connection,
            ]);
        }

        $directory = $configuration->get('directory');
        if (!empty($directory)) {
            if (!is_dir($directory)) {
                throw new ConfigurationException('Provided directory does not exist');
            }

            return ActionConfig::fromArray([
                'directory' => $directory,
            ]);
        }

        throw new ConfigurationException('Provided neither a connection nor a directory');
    }

    private function makeGetAllSchema(): SchemaCreate
    {
        $schema = new SchemaCreate();
        $schema->setName(self::SCHEMA_GET_ALL);
        $schema->setSource(SchemaBuilder::makeCollectionResponse(self::SCHEMA_GET_ALL, Parser::decodeAsObject((string) \file_get_contents(__DIR__ . '/schema/file.json'))));
        return $schema;
    }

    private function makeGetAllAction(ActionConfig $config): ActionCreate
    {
        $action = new ActionCreate();
        $action->setName(self::ACTION_GET_ALL);
        $action->setClass(FileDirectoryGetAll::class);
        $action->setConfig($config);
        return $action;
    }

    private function makeGetAction(ActionConfig $config): ActionCreate
    {
        $action = new ActionCreate();
        $action->setName(self::ACTION_GET);
        $action->setClass(FileDirectoryGet::class);
        $action->setConfig($config);
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
