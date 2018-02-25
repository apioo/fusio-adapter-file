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

namespace Fusio\Adapter\File\Connection;

use Fusio\Engine\ConnectionInterface;
use Fusio\Engine\Form\BuilderInterface;
use Fusio\Engine\Form\ElementFactoryInterface;
use Fusio\Engine\ParametersInterface;
use League\Flysystem\Adapter;
use League\Flysystem\Filesystem as Flysystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Filesystem
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Filesystem implements ConnectionInterface
{
    const TYPE_LOCAL = 'local';
    const TYPE_FTP   = 'ftp';

    public function getName()
    {
        return 'Filesystem';
    }

    /**
     * @param \Fusio\Engine\ParametersInterface $config
     * @return \League\Flysystem\FilesystemInterface
     */
    public function getConnection(ParametersInterface $config)
    {
        $settings = $config->get('config') ?: null;
        if (!empty($settings)) {
            $settings = Yaml::parse($settings);
        }

        return new Flysystem($this->newAdapter($config->get('type'), $settings));
    }

    public function configure(BuilderInterface $builder, ElementFactoryInterface $elementFactory)
    {
        $types = [
            self::TYPE_LOCAL => 'Local',
            self::TYPE_FTP   => 'FTP',
        ];

        $builder->add($elementFactory->newSelect('type', 'Type', $types));
        $builder->add($elementFactory->newTextArea('config', 'Config', 'yaml', 'The config of the selected type in YAML format. Click <a ng-click="help.showDialog(\'help/connection/filesystem.md\')">here</a> for more information.'));
    }

    /**
     * @param string $type
     * @param mixed $config
     * @return \League\Flysystem\Adapter\AbstractAdapter
     */
    private function newAdapter($type, $config)
    {
        switch ($type) {
            case self::TYPE_FTP:
                return new Adapter\Ftp(is_array($config) ? $config : []);

            case self::TYPE_LOCAL:
            default:
                return new Adapter\Local($config && is_string($config) ? $config : sys_get_temp_dir());
        }
    }
}
