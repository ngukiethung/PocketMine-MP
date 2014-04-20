<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

/**
 * Command handling related classes
 */
namespace pocketmine\command;

use pocketmine\Server;
use pocketmine\utils\TextFormat;

abstract class Command{
	/** @var string */
	private $name;

	/** @var string */
	private $nextLabel;

	/** @var string */
	private $label;

	/**
	 * @var string[]
	 */
	private $aliases = array();

	/**
	 * @var string[]
	 */
	private $activeAliases = array();

	/** @var CommandMap */
	private $commandMap = null;

	/** @var string */
	protected $description = "";

	/** @var string */
	protected $usageMessage;

	/** @var string */
	private $permission = null;

	/** @var string */
	private $permissionMessage = null;

	/**
	 * @param string   $name
	 * @param string   $description
	 * @param string   $usageMessage
	 * @param string[] $aliases
	 */
	public function __construct($name, $description = "", $usageMessage = null, array $aliases = array()){
		$this->name = $name;
		$this->nextLabel = $name;
		$this->label = $name;
		$this->description = $description;
		$this->usageMessage = $usageMessage === null ? "/" . $name : $usageMessage;
		$this->aliases = $aliases;
		$this->activeAliases = (array) $aliases;
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param string[]      $args
	 *
	 * @return mixed
	 */
	public abstract function execute(CommandSender $sender, $commandLabel, array $args);

	/**
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getPermission(){
		return $this->permission;
	}

	/**
	 * @param string|null $permission
	 */
	public function setPermission($permission){
		$this->permission = $permission;
	}

	/**
	 * @param CommandSender $target
	 *
	 * @return bool
	 */
	public function testPermission(CommandSender $target){
		if($this->testPermissionSilent($target)){
			return true;
		}

		if($this->permissionMessage === null){
			$target->sendMessage(TextFormat::RED . "You don't have permissions to use this command.");
		}elseif($this->permissionMessage !== ""){
			$target->sendMessage(str_replace("<permission>", $this->permission, $this->permissionMessage));
		}

		return false;
	}

	/**
	 * @param CommandSender $target
	 *
	 * @return bool
	 */
	public function testPermissionSilent(CommandSender $target){
		if($this->permission === null or $this->permission === ""){
			return true;
		}

		foreach(explode(";", $this->permission) as $permission){
			if($target->hasPermission($permission)){
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getLabel(){
		return $this->label;
	}

	public function setLabel($name){
		$this->nextLabel = $name;
		if(!$this->isRegistered()){
			$this->label = $name;

			return true;
		}

		return false;
	}

	/**
	 * Registers the command into a Command map
	 *
	 * @param CommandMap $commandMap
	 *
	 * @return bool
	 */
	public function register(CommandMap $commandMap){
		if($this->allowChangesFrom($commandMap)){
			$this->commandMap = $commandMap;

			return true;
		}

		return false;
	}

	/**
	 * @param CommandMap $commandMap
	 *
	 * @return bool
	 */
	public function unregister(CommandMap $commandMap){
		if($this->allowChangesFrom($commandMap)){
			$this->commandMap = null;
			$this->activeAliases = $this->aliases;
			$this->label = $this->nextLabel;

			return true;
		}

		return false;
	}

	/**
	 * @param CommandMap $commandMap
	 *
	 * @return bool
	 */
	private function allowChangesFrom(CommandMap $commandMap){
		return $this->commandMap === null or $this->commandMap === $commandMap;
	}

	/**
	 * @return bool
	 */
	public function isRegistered(){
		return $this->commandMap !== null;
	}

	/**
	 * @return string[]
	 */
	public function getAliases(){
		return $this->activeAliases;
	}

	/**
	 * @return string
	 */
	public function getPermissionMessage(){
		return $this->permissionMessage;
	}

	/**
	 * @return string
	 */
	public function getDescription(){
		return $this->description;
	}

	/**
	 * @return string
	 */
	public function getUsage(){
		return $this->usageMessage;
	}

	/**
	 * @param string[] $aliases
	 */
	public function setAliases(array $aliases){
		$this->aliases = $aliases;
		if(!$this->isRegistered()){
			$this->activeAliases = (array) $aliases;
		}
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description){
		$this->description = $description;
	}

	/**
	 * @param string $permissionMessage
	 */
	public function setPermissionMessage($permissionMessage){
		$this->permissionMessage = $permissionMessage;
	}

	/**
	 * @param string $usage
	 */
	public function setUsage($usage){
		$this->usageMessage = $usage;
	}

	/**
	 * TODO: static::broadcastCommandMessage()
	 *
	 * @param CommandSender $source
	 * @param string        $message
	 * @param bool          $sendToSource
	 */
	public static function broadcastCommandMessage(CommandSender $source, $message, $sendToSource = true){
		$result = $source->getName() . ": " . $message;

		//Command minecarts or command blocks are not implemented

		$users = Server::getInstance()->getPluginManager()->getPermissionSubscriptions(Server::BROADCAST_CHANNEL_ADMINISTRATIVE);
		$colored = TextFormat::GRAY . TextFormat::ITALIC . "[$result" . TextFormat::GRAY . TextFormat::ITALIC . "]";
		if($sendToSource === true and !($source instanceof ConsoleCommandSender)){
			$source->sendMessage($message);
		}

		foreach($users as $user){
			if($user instanceof CommandSender){
				if($user instanceof ConsoleCommandSender){
					$user->sendMessage($result);
				}elseif($user !== $source){
					$user->sendMessage($colored);
				}
			}
		}
	}
	
	/**
	 * @return string
	 */
	public function __toString(){
		return $this->name;
	}
}