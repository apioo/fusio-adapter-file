<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\File\Routes;

use Fusio\Adapter\File\Action\FileDirectoryDetail;
use Fusio\Adapter\File\Action\FileDirectoryIndex;
use Fusio\Engine\Factory\Resolver\PhpClass;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\Routes\ProviderInterface;
use Fusio\Engine\Routes\SetupInterface;

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

        $prefix = $this->getPrefix($basePath);

        $directoryIndexAction = $setup->addAction($prefix . '_Directory_Index', FileDirectoryIndex::class, PhpClass::class, [
            'directory' => $directory,
        ]);

        $directoryDetailAction = $setup->addAction($prefix . '_Directory_Detail', FileDirectoryDetail::class, PhpClass::class, [
            'directory' => $directory,
        ]);

        $schemaParameters = $setup->addSchema('File_Directory_Index_Parameters', $this->schemaBuilder->getParameters());
        $schemaResponse = $setup->addSchema('File_Directory_Index_Response', $this->schemaBuilder->getResponse());

        $setup->addRoute(1, '/', 'Fusio\Impl\Controller\SchemaApiController', [], [
            [
                'version' => 1,
                'methods' => [
                    'GET' => [
                        'active' => true,
                        'public' => true,
                        'description' => 'Returns a collection of files',
                        'parameters' => $schemaParameters,
                        'responses' => [
                            200 => $schemaResponse,
                        ],
                        'action' => $directoryIndexAction,
                    ],
                ],
            ]
        ]);

        $setup->addRoute(1, '/:id', 'Fusio\Impl\Controller\SchemaApiController', [], [
            [
                'version' => 1,
                'methods' => [
                    'GET' => [
                        'active' => true,
                        'public' => true,
                        'description' => 'Returns a single file',
                        'responses' => [
                            200 => -1,
                        ],
                        'action' => $directoryDetailAction,
                    ],
                ],
            ]
        ]);
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory): void
    {
        $builder->add($elementFactory->newInput('directory', 'Directory', 'text', 'A path to a directory which you want expose'));
    }

    private function getPrefix(string $path): string
    {
        return implode('_', array_map('ucfirst', array_filter(explode('/', $path))));
    }
}
