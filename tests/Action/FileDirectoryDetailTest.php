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

use Fusio\Adapter\Fcgi\Tests\FileTestCase;
use Fusio\Adapter\File\Action\FileDirectoryDetail;
use PSX\DateTime\DateTime;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * FileDirectoryDetailTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectoryDetailTest extends FileTestCase
{
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
        $this->assertEquals($this->getExpectHeaders(__DIR__ . '/../foo/test_semicolon.csv'), $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }

    private function getExpectHeaders(string $file): array
    {
        return [
            'last-modified' => date(DateTime::HTTP, filemtime($file)),
            'etag' => '"' . sha1_file($file) . '"'
        ];
    }
}
