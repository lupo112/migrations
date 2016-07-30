<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\SymfonyConsole;

use Nette\Utils\Strings;
use Nextras;
use Nextras\Migrations\Entities\Group;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CreateCommand extends BaseCommand
{
	protected function configure()
	{
		$this->setName('migrations:create');
		$this->setDescription('Creates new migration file with proper name (e.g. 2015-03-14-130836-label.sql)');
		$this->setHelp('Prints path of the created file to standard output.');
		$this->addArgument('type', InputArgument::REQUIRED, $this->getTypeArgDescription());
		$this->addArgument('label', InputArgument::REQUIRED, 'short description');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$dir = $this->getDirectory($input->getArgument('type'));
		$name = $this->getFileName($input->getArgument('label'));

		if ($this->hasNumericSubdirectory($dir, $foundYear)) {
			if ($this->hasNumericSubdirectory($foundYear, $foundMonth)) {
				$file = $dir . date('/Y/m/') . $name;
			} else {
				$file = $dir . date('/Y/') . $name;
			}
		} else {
			$file = "$dir/$name";
		}

		@mkdir(dirname($file), 0777, TRUE); // directory may already exist
		touch($file);
		$output->writeln($file);
	}


	/**
	 * @param  string $type
	 * @return string
	 */
	private function getDirectory($type)
	{
		foreach ($this->config->getGroups() as $group) {
			if (Strings::startsWith($group->name, $type)) {
				return $group->directory;
			}
		}

		throw new Nextras\Migrations\LogicException("Unknown type '$type' given, expected on of 's', 'b' or 'd'.");
	}


	/**
	 * @param  string $label
	 * @return string
	 */
	private function getFileName($label)
	{
		return date('Y-m-d-His-') . Strings::webalize($label, '.') . '.sql';
	}


	/**
	 * @param  string $dir
	 * @param  string|NULL $found
	 * @return bool
	 */
	private function hasNumericSubdirectory($dir, & $found)
	{
		$items = @scandir($dir); // directory may not exist
		foreach ($items as $item) {
			if ($item !== '.' && $item !== '..' && is_dir($dir . '/' . $item)) {
				$found = $dir . '/' . $item;
				return TRUE;
			}
		}
		return FALSE;
	}


	/**
	 * @return string
	 */
	private function getTypeArgDescription()
	{
		$options = [];
		$groups = $this->config->getGroups();
		usort($groups, function (Group $a, Group $b) {
			return strcmp($a->name, $b->name);
		});

		foreach ($groups as $i => $group) {
			for ($j = 1; $j < strlen($group->name); $j++) {
				if (!isset($groups[$i + 1]) || strncmp($group->name, $groups[$i + 1]->name, $j) !== 0) {
					$options[] = substr($group->name, 0, $j) . '(' . substr($group->name, $j) . ')';
					break;
				}
			}
		}

		return implode(' or ', array_filter([
			implode(', ', array_slice($options, 0, -1)),
			array_slice($options, -1)[0],
		]));
	}

}
