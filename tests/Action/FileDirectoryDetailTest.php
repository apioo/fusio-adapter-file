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

use Fusio\Adapter\File\Action\FileDirectoryGet;
use Fusio\Adapter\File\Tests\FileTestCase;
use PSX\Http\Environment\HttpResponseInterface;
use PSX\Http\Writer;

/**
 * FileDirectoryDetailTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectoryDetailTest extends FileTestCase
{
    public function testHandle()
    {
        $action = $this->getActionFactory()->factory(FileDirectoryGet::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET', ['id' => 'e13fe597-537e-36c2-b99a-d652c3021a36']),
            $this->getParameters(['directory' => __DIR__ . '/../foo']),
            $this->getContext()
        );

        /** @var Writer\Resource $body */
        $body = $response->getBody();

        $actual = stream_get_contents($body->getData());
        $expect = 'id;name' . "\n";
        $expect.= '1;foo' . "\n";
        $expect.= '2;bar';

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/../foo/test_semicolon.csv'), $response->getHeaders());
        $this->assertEquals($expect, $actual, $actual);
    }

    private function getExpectHeaders(string $file): array
    {
        return [
            'last-modified' => date(\DateTimeInterface::RFC3339, filemtime($file)),
            'etag' => '"' . md5_file($file) . '"'
        ];
    }
}
