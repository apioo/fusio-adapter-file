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

namespace Fusio\Adapter\File\Tests\Action;

use Fusio\Adapter\File\Action\FileDirectoryDetail;
use Fusio\Adapter\File\Action\FileDirectoryIndex;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PHPUnit\Framework\TestCase;
use PSX\DateTime\DateTime;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * FileDirectoryDetailTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectoryDetailTest extends TestCase
{
    use EngineTestCaseTrait;

    public function testHandle()
    {
        $action = $this->getActionFactory()->factory(FileDirectoryDetail::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET', ['id' => 'e13fe597-537e-36c2-b99a-d652c3021a36']),
            $this->getParameters(['directory' => __DIR__ . '/../foo']),
            $this->getContext()
        );

        $actual = json_encode($response->getBody(), JSON_PRETTY_PRINT);
        $expect = <<<JSON
{
    "fileName": "test_semicolon.csv",
    "content": [
        [
            "id",
            "name"
        ],
        [
            "1",
            "foo"
        ],
        [
            "2",
            "bar"
        ]
    ]
}
JSON;

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'last-modified' => date(DateTime::HTTP, filemtime(__DIR__ . '/../foo/test_semicolon.csv')),
            'etag' => '"759c145ff96ed97db41dfa923a0a9fa71f058dbe"',
        ], $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }
}
