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

namespace Fusio\Adapter\File\Tests\Action;

use Fusio\Adapter\File\Action\FileProcessor;
use Fusio\Adapter\File\Tests\FileTestCase;
use Fusio\Engine\Form\Builder;
use Fusio\Engine\Form\Container;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Http\Writer;
use Symfony\Component\Yaml\Yaml;

/**
 * FileProcessorTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class FileProcessorTest extends FileTestCase
{
    public function testHandle()
    {
        $action = $this->getActionFactory()->factory(FileProcessor::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET'),
            $this->getParameters(['file' => __DIR__ . '/../foo/response.json']),
            $this->getContext()
        );

        /** @var Writer\Resource $body */
        $body = $response->getBody();

        $actual = stream_get_contents($body->getData());
        $expect = <<<JSON
{
  "foo": "bar",
  "bar": "foo"
}
JSON;

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/../foo/response.json'), $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testHandleJson()
    {
        $action = $this->getActionFactory()->factory(FileProcessor::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET'),
            $this->getParameters(['file' => __DIR__ . '/../foo/response.json']),
            $this->getContext()
        );

        /** @var Writer\Resource $body */
        $body = $response->getBody();

        $actual = stream_get_contents($body->getData());
        $expect = <<<JSON
{
  "foo": "bar",
  "bar": "foo"
}
JSON;

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/../foo/response.json'), $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    public function testHandleYaml()
    {
        $action = $this->getActionFactory()->factory(FileProcessor::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET'),
            $this->getParameters(['file' => __DIR__ . '/../foo/response.yaml']),
            $this->getContext()
        );

        /** @var Writer\Resource $body */
        $body = $response->getBody();

        $actual = stream_get_contents($body->getData());
        $actual = json_encode(Yaml::parse($actual));
        $expect = <<<YAML
foo: "bar"
bar: "foo"
YAML;

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/../foo/response.yaml'), $response->getHeaders());
        $this->assertJsonStringEqualsJsonString(json_encode(Yaml::parse($expect)), $actual, $actual);
    }

    public function testHandleTxt()
    {
        $action = $this->getActionFactory()->factory(FileProcessor::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET'),
            $this->getParameters(['file' => __DIR__ . '/../foo/response.txt']),
            $this->getContext()
        );

        /** @var Writer\Resource $body */
        $body = $response->getBody();

        $actual = stream_get_contents($body->getData());
        $expect = 'foobar' . "\n";

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/../foo/response.txt'), $response->getHeaders());
        $this->assertEquals($expect, $actual, $actual);
    }

    public function testHandleIfNoneMatch()
    {
        $action = $this->getActionFactory()->factory(FileProcessor::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET', [], [], ['If-None-Match' => '"' . md5_file(__DIR__ . '/../foo/response.txt') . '"']),
            $this->getParameters(['file' => __DIR__ . '/../foo/response.txt']),
            $this->getContext()
        );

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/../foo/response.txt'), $response->getHeaders());
    }

    public function testHandleIfModifiedSince()
    {
        $action = $this->getActionFactory()->factory(FileProcessor::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET', [], [], ['If-Modified-Since' => date(\DateTime::RFC7231, time() + 3600)]),
            $this->getParameters(['file' => __DIR__ . '/../foo/response.txt']),
            $this->getContext()
        );

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/../foo/response.txt'), $response->getHeaders());
    }

    public function testGetForm()
    {
        $action  = $this->getActionFactory()->factory(FileProcessor::class);
        $builder = new Builder();
        $factory = $this->getFormElementFactory();

        $action->configure($builder, $factory);

        $this->assertInstanceOf(Container::class, $builder->getForm());
    }

    private function getExpectHeaders(string $file): array
    {
        return [
            'last-modified' => date(\DateTimeInterface::RFC3339, filemtime($file)),
            'etag' => '"' . md5_file($file) . '"'
        ];
    }
}
