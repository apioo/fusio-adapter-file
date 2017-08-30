<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2017 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Adapter\File\Tests\Action;

use Fusio\Adapter\File\Action\FileProcessor;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\ResponseInterface;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PSX\DateTime\DateTime;

/**
 * FileProcessorTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class FileProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EngineTestCaseTrait;

    protected function setUp()
    {
        parent::setUp();
    }

    public function testHandle()
    {
        $action = $this->getActionFactory()->factory(FileProcessor::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET'),
            $this->getParameters(['file' => __DIR__ . '/response.json']),
            $this->getContext()
        );

        $actual = json_encode($response->getBody(), JSON_PRETTY_PRINT);
        $expect = <<<JSON
{
    "foo": "bar",
    "bar": "foo"
}
JSON;

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/response.json'), $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(FileProcessor::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }

    private function getExpectHeaders($file)
    {
        return [
            'last-modified' => date(DateTime::HTTP, filemtime($file)),
            'etag' => '"' . sha1_file($file) . '"'
        ];
    }
}
