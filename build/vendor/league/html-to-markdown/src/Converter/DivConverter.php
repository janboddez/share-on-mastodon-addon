<?php

declare (strict_types=1);
namespace Share_On_Mastodon\League\HTMLToMarkdown\Converter;

use Share_On_Mastodon\League\HTMLToMarkdown\Configuration;
use Share_On_Mastodon\League\HTMLToMarkdown\ConfigurationAwareInterface;
use Share_On_Mastodon\League\HTMLToMarkdown\ElementInterface;
/** @internal */
class DivConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /** @var Configuration */
    protected $config;
    public function setConfig(Configuration $config) : void
    {
        $this->config = $config;
    }
    public function convert(ElementInterface $element) : string
    {
        if ($this->config->getOption('strip_tags', \false)) {
            return $element->getValue() . "\n\n";
        }
        return \html_entity_decode($element->getChildrenAsString());
    }
    /**
     * @return string[]
     */
    public function getSupportedTags() : array
    {
        return ['div'];
    }
}
