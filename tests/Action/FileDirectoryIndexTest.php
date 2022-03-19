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
        $actual = preg_replace('/([0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2})/', '0000-00-00T00:00:00', $actual);
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
            "lastModified": "0000-00-00T00:00:00+00:00"
        },
        {
            "id": "11cce436-5475-3a2b-ae62-3050c552bc71",
            "fileName": "response.json",
            "size": 34,
            "contentType": "application\/json",
            "sha1": "2f21bd703e135f7c3daf9ee201552e1e83326665",
            "lastModified": "0000-00-00T00:00:00+00:00"
        },
        {
            "id": "204bdfd3-03d7-3b08-8ab1-05cbd3642b4f",
            "fileName": "response.txt",
            "size": 7,
            "contentType": "text\/plain",
            "sha1": "988881adc9fc3655077dc2d4d757d480b5ea0e11",
            "lastModified": "0000-00-00T00:00:00+00:00"
        },
        {
            "id": "ed35cd2e-e450-3247-ad84-e4248d11a484",
            "fileName": "response.yaml",
            "size": 22,
            "contentType": "text\/plain",
            "sha1": "34673b5a4ecb6d85c9ff1d6e391a4455d3d05e13",
            "lastModified": "0000-00-00T00:00:00+00:00"
        },
        {
            "id": "13ae3bc7-01ac-3199-b2c3-939b0fdc1682",
            "fileName": "test_comma.csv",
            "size": 19,
            "contentType": "text\/plain",
            "sha1": "877662089544dce80691af4c7c55610161f03fd8",
            "lastModified": "0000-00-00T00:00:00+00:00"
        },
        {
            "id": "e13fe597-537e-36c2-b99a-d652c3021a36",
            "fileName": "test_semicolon.csv",
            "size": 19,
            "contentType": "text\/plain",
            "sha1": "759c145ff96ed97db41dfa923a0a9fa71f058dbe",
            "lastModified": "0000-00-00T00:00:00+00:00"
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
