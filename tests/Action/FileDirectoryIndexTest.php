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
        $actual = preg_replace('/([0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2})/', '0000-00-00T00:00:00', $actual);
        $expect = <<<JSON
{
    "totalResults": 6,
    "itemsPerPage": 16,
    "startIndex": 0,
    "entry": [
        {
            "id": "32643094-bf7c-3706-b004-b2312033b98e",
            "name": "bar.txt",
            "size": 6,
            "contentType": "text\/plain",
            "checksum": "3858f62230ac3c915f300c664312c63f",
            "lastModified": "0000-00-00T00:00:00Z"
        },
        {
            "id": "11cce436-5475-3a2b-ae62-3050c552bc71",
            "name": "response.json",
            "size": 34,
            "contentType": "application\/json",
            "checksum": "d9e2a02f395da244bf2c7e9191ddef7d",
            "lastModified": "0000-00-00T00:00:00Z"
        },
        {
            "id": "204bdfd3-03d7-3b08-8ab1-05cbd3642b4f",
            "name": "response.txt",
            "size": 7,
            "contentType": "text\/plain",
            "checksum": "14758f1afd44c09b7992073ccf00b43d",
            "lastModified": "0000-00-00T00:00:00Z"
        },
        {
            "id": "ed35cd2e-e450-3247-ad84-e4248d11a484",
            "name": "response.yaml",
            "size": 22,
            "contentType": "text\/yaml",
            "checksum": "fec6b0544a0d25364b74fe5841c5d4cb",
            "lastModified": "0000-00-00T00:00:00Z"
        },
        {
            "id": "13ae3bc7-01ac-3199-b2c3-939b0fdc1682",
            "name": "test_comma.csv",
            "size": 19,
            "contentType": "text\/csv",
            "checksum": "192fabf272dfe2329c977bd18be1b6cb",
            "lastModified": "0000-00-00T00:00:00Z"
        },
        {
            "id": "e13fe597-537e-36c2-b99a-d652c3021a36",
            "name": "test_semicolon.csv",
            "size": 19,
            "contentType": "text\/csv",
            "checksum": "78e7a8c69c2f8253c6732a30482cfef5",
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
