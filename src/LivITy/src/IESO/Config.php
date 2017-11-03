<?php
/*
 * This file is part of ieso-tool - the ieso query and download tool.
 *
 * (c) LivITy Consultinbg Ltd, Enbridge Inc., Kevin Edwards
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace LivITy\IESO;
use Dotenv\Dotenv;

class Config
{
    protected $config_file;
    protected $env;

    /**
	 * @param array $container
	 */
	public function __construct(String $root)
    {
        $dotenv = new Dotenv($root);
        $dotenv->load();
        $dotenv->required([
            'IESO_AUTH_USER', 'IESO_AUTH_PASSWORD', 'IESO_APP_ENV', 'IESO_ROOT_PATH',
            'IESO_BASE_URI', 'IESO_ENBRIDGE_PATH'
        ]);

        $env_config = $root . '\\env\\' . \Env::get('IESO_APP_ENV')  . '.ini';
        if (file_exists($env_config)) {
            require_once $env_config;
        }

        return $this;
    }
}
