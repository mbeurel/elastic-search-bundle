<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Austral ElasticSearch Configuration.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Configuration implements ConfigurationInterface
{
  /**
   * {@inheritdoc}
   */
  public function getConfigTreeBuilder(): TreeBuilder
  {
    $treeBuilder = new TreeBuilder('austral_elastic_search');
    $rootNode = $treeBuilder->getRootNode();

    $node = $rootNode->children();
    $node = $node->booleanNode("enabled")->end()
      ->arrayNode("logger")
        ->addDefaultsIfNotSet()
        ->children()
          ->booleanNode("enabled")->end()
        ->end()
      ->end()
      ->scalarNode("index_name")->end()
      ->arrayNode("hosts")
        ->scalarPrototype()->end()
      ->end();

    /*$node = $this->buildVars($node
      ->arrayNode('hosts')
      ->arrayPrototype()
    );
    $node->end();
*/

    return $treeBuilder;
  }

  /**
   * @param ArrayNodeDefinition $node
   *
   * @return mixed
   */
  protected function buildVars(ArrayNodeDefinition $node)
  {
    return $node->children()
      ->end();
  }

  /**
   * @return array
   */
  public function getConfigDefault(): array
  {
    return array(
      "enabled"           =>  false,
      "logger"            =>  array(
        "enabled"           =>  true,
      ),
      "index_name"        =>  "elastic-search",
      "hosts"             =>  array(
      )
    );
  }

}
