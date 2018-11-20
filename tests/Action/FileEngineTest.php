<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

use Fusio\Adapter\File\Action\FileEngine;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PSX\DateTime\DateTime;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Http\Writer;

/**
 * FileEngineTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class FileEngineTest extends \PHPUnit_Framework_TestCase
{
    use EngineTestCaseTrait;

    protected function setUp()
    {
        parent::setUp();
    }

    public function testHandleJson()
    {
        $action = $this->getActionFactory()->factory(FileEngine::class);
        $action->setFile(__DIR__ . '/response.json');

        // handle request
        $response = $action->handle(
            $this->getRequest('GET'),
            $this->getParameters(),
            $this->getContext()
        );

        $actual = json_encode($response->getBody(), JSON_PRETTY_PRINT);
        $expect = <<<JSON
{
    "foo": "bar",
    "bar": "foo"
}
JSON;

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/response.json'), $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testHandleYaml()
    {
        $action = $this->getActionFactory()->factory(FileEngine::class);
        $action->setFile(__DIR__ . '/response.yaml');

        // handle request
        $response = $action->handle(
            $this->getRequest('GET'),
            $this->getParameters(),
            $this->getContext()
        );

        $actual = json_encode($response->getBody(), JSON_PRETTY_PRINT);
        $expect = <<<JSON
{
    "foo": "bar",
    "bar": "foo"
}
JSON;

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/response.yaml'), $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testHandleTxt()
    {
        $action = $this->getActionFactory()->factory(FileEngine::class);
        $action->setFile(__DIR__ . '/response.txt');

        // handle request
        $response = $action->handle(
            $this->getRequest('GET'),
            $this->getParameters(),
            $this->getContext()
        );

        /** @var File $body */
        $body = $response->getBody();

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/response.txt'), $response->getHeaders());
        $this->assertInstanceOf(Writer\File::class, $body);
    }

    public function testHandleIfNoneMatch()
    {
        $action = $this->getActionFactory()->factory(FileEngine::class);
        $action->setFile(__DIR__ . '/response.txt');

        // handle request
        $response = $action->handle(
            $this->getRequest('GET', [], [], ['If-None-Match' => '"' . sha1_file(__DIR__ . '/response.txt') . '"']),
            $this->getParameters(),
            $this->getContext()
        );

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/response.txt'), $response->getHeaders());
    }

    public function testHandleIfModifiedSince()
    {
        $action = $this->getActionFactory()->factory(FileEngine::class);
        $action->setFile(__DIR__ . '/response.txt');

        // handle request
        $response = $action->handle(
            $this->getRequest('GET', [], [], ['If-Modified-Since' => 'Wed, 12 Aug 2020 17:41:56 GMT']),
            $this->getParameters(),
            $this->getContext()
        );

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/response.txt'), $response->getHeaders());
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(FileEngine::class);
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
