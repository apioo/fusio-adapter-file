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

use Fusio\Adapter\File\Action\FileDirectoryGetAll;
use Fusio\Adapter\File\Tests\FileTestCase;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * FileDirectoryIndexTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectoryIndexTest extends FileTestCase
{
    public function testHandle()
    {
        $action = $this->getActionFactory()->factory(FileDirectoryGetAll::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET'),
            $this->getParameters(['directory' => __DIR__ . '/../foo']),
            $this->getContext()
        );

        if (PHP_VERSION_ID >= 80400) {
            $expectContentType = 'text/csv';
        } else {
            $expectContentType = 'text/plain';
        }

        $actual = json_encode($response->getBody(), JSON_PRETTY_PRINT);
        $actual = preg_replace('/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/', '00000000-0000-0000-0000-000000000000', $actual);
        $actual = preg_replace('/([0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2})/', '0000-00-00T00:00:00', $actual);
        $actual = preg_replace('/([0-9a-f]{32})/', '00000000000000000000000000000000', $actual);
        $expect = <<<JSON
{
    "totalResults": 6,
    "itemsPerPage": 16,
    "startIndex": 0,
    "entry": [
        {
            "id": "00000000-0000-0000-0000-000000000000",
            "name": "bar.txt",
            "size": 6,
            "contentType": "text\/plain",
            "checksum": "00000000000000000000000000000000",
            "lastModified": "0000-00-00T00:00:00Z"
        },
        {
            "id": "00000000-0000-0000-0000-000000000000",
            "name": "response.json",
            "size": 34,
            "contentType": "application\/json",
            "checksum": "00000000000000000000000000000000",
            "lastModified": "0000-00-00T00:00:00Z"
        },
        {
            "id": "00000000-0000-0000-0000-000000000000",
            "name": "response.txt",
            "size": 7,
            "contentType": "text\/plain",
            "checksum": "00000000000000000000000000000000",
            "lastModified": "0000-00-00T00:00:00Z"
        },
        {
            "id": "00000000-0000-0000-0000-000000000000",
            "name": "response.yaml",
            "size": 22,
            "contentType": "text\/yaml",
            "checksum": "00000000000000000000000000000000",
            "lastModified": "0000-00-00T00:00:00Z"
        },
        {
            "id": "00000000-0000-0000-0000-000000000000",
            "name": "test_comma.csv",
            "size": 19,
            "contentType": "{$expectContentType}",
            "checksum": "00000000000000000000000000000000",
            "lastModified": "0000-00-00T00:00:00Z"
        },
        {
            "id": "00000000-0000-0000-0000-000000000000",
            "name": "test_semicolon.csv",
            "size": 19,
            "contentType": "text\/csv",
            "checksum": "00000000000000000000000000000000",
            "lastModified": "0000-00-00T00:00:00Z"
        }
    ]
}
JSON;

        $this->assertInstanceOf(HttpResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertJsonStringEqualsJsonString($expect, $actual, $actual);
    }
}
