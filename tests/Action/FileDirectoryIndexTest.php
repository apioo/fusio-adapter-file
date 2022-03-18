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

use Fusio\Adapter\File\Action\FileDirectoryIndex;
use Fusio\Engine\Test\EngineTestCaseTrait;
use PHPUnit\Framework\TestCase;
use PSX\Http\Environment\HttpResponseInterface;

/**
 * FileDirectoryIndexTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org/
 */
class FileDirectoryIndexTest extends TestCase
{
    use EngineTestCaseTrait;

    public function testHandle()
    {
        $action = $this->getActionFactory()->factory(FileDirectoryIndex::class);

        // handle request
        $response = $action->handle(
            $this->getRequest('GET'),
            $this->getParameters(['directory' => __DIR__ . '/../foo']),
            $this->getContext()
        );

        $actual = json_encode($response->getBody(), JSON_PRETTY_PRINT);
        $expect = <<<JSON
{
    "totalResults": 6,
    "itemsPerPage": 16,
    "startIndex": 0,
    "entry": [
        {
            "id": "32643094-bf7c-3706-b004-b2312033b98e",
            "fileName": "bar.txt",
            "size": 6,
            "contentType": "text\/plain",
            "sha1": "8843d7f92416211de9ebb963ff4ce28125932878",
            "lastModified": "2017-10-21T11:54:28+00:00"
        },
        {
            "id": "11cce436-5475-3a2b-ae62-3050c552bc71",
            "fileName": "response.json",
            "size": 37,
            "contentType": "application\/json",
            "sha1": "39cdad79d91a587454cd8a4c78eaa6d6901f5e15",
            "lastModified": "2017-08-30T17:41:34+00:00"
        },
        {
            "id": "204bdfd3-03d7-3b08-8ab1-05cbd3642b4f",
            "fileName": "response.txt",
            "size": 8,
            "contentType": "text\/plain",
            "sha1": "60e644a56cb3048e15e62d88e311c28e5a4f6d28",
            "lastModified": "2017-08-30T17:41:56+00:00"
        },
        {
            "id": "ed35cd2e-e450-3247-ad84-e4248d11a484",
            "fileName": "response.yaml",
            "size": 24,
            "contentType": "text\/plain",
            "sha1": "1195ef0aef4c9a7d72cd998f5ad740156920ffe4",
            "lastModified": "2017-08-30T17:41:49+00:00"
        },
        {
            "id": "13ae3bc7-01ac-3199-b2c3-939b0fdc1682",
            "fileName": "test_comma.csv",
            "size": 21,
            "contentType": "text\/plain",
            "sha1": "8e1ec5e0dfd927c4dbc7e5e5b29ca5008c08cc24",
            "lastModified": "2022-03-18T21:57:57+00:00"
        },
        {
            "id": "e13fe597-537e-36c2-b99a-d652c3021a36",
            "fileName": "test_semicolon.csv",
            "size": 21,
            "contentType": "text\/plain",
            "sha1": "b5ba697f931678d3d42dbf9f9be55c7021c26622",
            "lastModified": "2022-03-18T21:57:43+00:00"
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
