<?php

declare (strict_types=1);
namespace Share_On_Mastodon\League\HTMLToMarkdown\Converter;

use Share_On_Mastodon\League\HTMLToMarkdown\ElementInterface;
class HorizontalRuleConverter implements ConverterInterface
{
    public function convert(ElementInterface $element) : string
    {
        return "---\n\n";
    }
    /**
     * @return string[]
     */
    public function getSupportedTags() : array
    {
        return ['hr'];
    }
}
