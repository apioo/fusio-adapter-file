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

namespace Fusio\Adapter\File\Generator;

use Fusio\Adapter\File\Action\FileDirectoryDetail;
use Fusio\Adapter\File\Action\FileDirectoryIndex;
use Fusio\Engine\Factory\Resolver\PhpClass;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\Generator\ProviderInterface;
use Fusio\Engine\Generator\SetupInterface;
use Fusio\Model\Backend\Action;
use Fusio\Model\Backend\ActionConfig;
use Fusio\Model\Backend\Operation;
use Fusio\Model\Backend\OperationParameters;
use Fusio\Model\Backend\OperationSchema;
use Fusio\Model\Backend\Schema;
use Fusio\Model\Backend\SchemaSource;

/**
 * FileDirectory
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectory implements ProviderInterface
{
    private SchemaBuilder $schemaBuilder;

    public function __construct()
    {
        $this->schemaBuilder = new SchemaBuilder();
    }

    public function getName(): string
    {
        return 'File-Directory';
    }

    public function setup(SetupInterface $setup, string $basePath, ParametersInterface $configuration): void
    {
        $directory = $configuration->get('directory');
        if (!is_dir($directory)) {
            throw new \RuntimeException('Provided directory does not exist');
        }

        $setup->addSchema($this->makeIndexSchema());

        $setup->addAction($this->makeIndexAction($directory));
        $setup->addAction($this->makeDetailAction($directory));

        $setup->addOperation($this->makeIndexOperation());
        $setup->addOperation($this->makeDetailOperation());
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('directory', 'Directory', 'text', 'A path to a directory which you want expose'));
    }

    private function makeIndexSchema(): Schema
    {
        $schema = new Schema();
        $schema->setName('File_Index_Response');
        $schema->setSource(SchemaSource::from(\json_decode(file_get_contents(__DIR__ . '/schema/file-directory/response.json'))));
        return $schema;
    }

    private function makeIndexAction(string $directory): Action
    {
        $action = new Action();
        $action->setName('File_Index');
        $action->setClass(FileDirectoryIndex::class);
        $action->setEngine(PhpClass::class);
        $action->setConfig(ActionConfig::fromArray([
            'directory' => $directory,
        ]));
        return $action;
    }

    private function makeDetailAction(string $directory): Action
    {
        $action = new Action();
        $action->setName('File_Detail');
        $action->setClass(FileDirectoryDetail::class);
        $action->setEngine(PhpClass::class);
        $action->setConfig(ActionConfig::fromArray([
            'directory' => $directory,
        ]));
        return $action;
    }

    private function makeIndexOperation(): Operation
    {
        $startIndexSchema = new OperationSchema();
        $startIndexSchema->setType('integer');

        $countSchema = new OperationSchema();
        $countSchema->setType('integer');

        $sortBySchema = new OperationSchema();
        $sortBySchema->setType('string');

        $sortOrderSchema = new OperationSchema();
        $sortOrderSchema->setType('string');

        $filterBySchema = new OperationSchema();
        $filterBySchema->setType('string');

        $filterOpSchema = new OperationSchema();
        $filterOpSchema->setType('string');

        $filterValueSchema = new OperationSchema();
        $filterValueSchema->setType('string');

        $parameters = new OperationParameters();
        $parameters->put('startIndex', $startIndexSchema);
        $parameters->put('count', $countSchema);
        $parameters->put('sortBy', $sortBySchema);
        $parameters->put('sortOrder', $sortOrderSchema);
        $parameters->put('filterBy', $filterBySchema);
        $parameters->put('filterOp', $filterOpSchema);
        $parameters->put('filterValue', $filterValueSchema);

        $operation = new Operation();
        $operation->setName('getAll');
        $operation->setDescription('Returns a collection of files');
        $operation->setHttpMethod('GET');
        $operation->setHttpPath('/');
        $operation->setHttpCode(200);
        $operation->setParameters($parameters);
        $operation->setOutgoing('File_Index_Response');
        return $operation;
    }

    private function makeDetailOperation(): Operation
    {
        $operation = new Operation();
        $operation->setName('get');
        $operation->setDescription('Returns a single file');
        $operation->setHttpMethod('GET');
        $operation->setHttpPath('/:id');
        $operation->setHttpCode(200);
        $operation->setOutgoing('Passthru');

        return $operation;
    }

}
