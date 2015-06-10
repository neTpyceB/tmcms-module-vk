<?php
namespace neTpyceB\TMCms\Modules\Vk;

use neTpyceB\TMCms\Modules\CommonObject;
use neTpyceB\TMCms\Modules\IModule;

defined('INC') or exit;

class ModuleVk implements IModule {
	/** @var $this */
	private static $instance;

	public static function getInstance() {
		if (!self::$instance) self::$instance = new self;
		return self::$instance;
	}


}