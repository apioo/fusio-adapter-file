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

namespace Fusio\Adapter\File\Tests\Connection;

use Fusio\Adapter\File\Connection\Filesystem;
use Fusio\Adapter\File\Tests\FileTestCase;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Form\Element\Input;
use Fusio\Engine\Parameters;
use League\Flysystem\FilesystemOperator;

/**
 * FilesystemTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class FilesystemTest extends FileTestCase
{
    public function testGetConnection()
    {
        /** @var Filesystem $connectionFactory */
        $connectionFactory = $this->getConnectionFactory()->factory(Filesystem::class);

        $config = new Parameters([
            'config' => __DIR__ . '/../foo',
        ]);

        $filesystem = $connectionFactory->getConnection($config);

        $this->assertInstanceOf(FilesystemOperator::class, $filesystem);
        $this->assertEquals('foobar', $filesystem->read('bar.txt'));
    }

    public function testConfigure()
    {
        $connection = $this->getConnectionFactory()->factory(Filesystem::class);
        $builder    = new Builder();
        $factory    = $this->getFormElementFactory();

        $connection->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());

        $elements = $builder->getForm()->getElements();
        $this->assertEquals(1, count($elements));
        $this->assertInstanceOf(Input::class, $elements[0]);
    }
}
