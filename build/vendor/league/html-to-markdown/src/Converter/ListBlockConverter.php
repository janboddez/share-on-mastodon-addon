<?php

declare (strict_types=1);
namespace Share_On_Mastodon\League\HTMLToMarkdown\Converter;

use Share_On_Mastodon\League\HTMLToMarkdown\ElementInterface;
/** @internal */
class ListBlockConverter implements ConverterInterface
{
    public function convert(ElementInterface $element) : string
    {
        return $element->getValue() . "\n";
    }
    /**
     * @return string[]
     */
    public function getSupportedTags() : array
    {
        return ['ol', 'ul'];
    }
}
