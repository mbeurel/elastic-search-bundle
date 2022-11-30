<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Command;

use Austral\ToolsBundle\Command\Base\Command;
use Austral\ToolsBundle\Command\Exception\CommandException;

use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Austral Initialise ElasticSearch Command.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ElasticSearchCommand extends Command
{

  /**
   * @var string
   */
  protected static $defaultName = 'austral:elastic-search';

  /**
   * @var string
   */
  protected string $titleCommande = "Elastic Search command";

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setDefinition([
        new InputOption('--drop', '-d', InputOption::VALUE_NONE, 'Drop Elastic Search index'),
        new InputOption('--create', '-c', InputOption::VALUE_NONE, 'Create Elastic Search index'),
        new InputOption('--hydrate', '', InputOption::VALUE_NONE, 'Hydrate Elastic Search index'),
        new InputOption('--search', '', InputOption::VALUE_REQUIRED, 'Hydrate Elastic Search index'),
      ])
      ->setDescription($this->titleCommande)
      ->setHelp(<<<'EOF'
The <info>%command.name%</info> command to Elastic Search index

  <info>php %command.full_name% --drop</info>
  <info>php %command.full_name% --create</info>
  
  <info>php %command.full_name% -d</info>
  <info>php %command.full_name% -c</info>
EOF
      )
    ;
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   *
   * @throws CommandException
   * @throws Exception
   * @throws NonUniqueResultException
   */
  protected function executeCommand(InputInterface $input, OutputInterface $output)
  {
    try {
      $elasticSearch = $this->container->get('austral.elastic_search')->setIo($this->io);
      if($input->getOption("drop"))
      {
        $elasticSearch->dropIndex();
      }
      if($input->getOption("create"))
      {
        $elasticSearch->createIndex();
      }
      if($input->getOption("hydrate"))
      {
        $elasticSearch->hydrate();
      }
      if($query = $input->getOption("search"))
      {
        $searchResult = $elasticSearch->searchByQuery($query);
        dump($searchResult);
      }
    }
    catch (Exception $e) {
      throw new Exception("Elastic search error -> {$e->getMessage()} !!!");
    }
  }

}