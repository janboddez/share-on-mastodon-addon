<?php

declare (strict_types=1);
namespace Share_On_Mastodon\League\HTMLToMarkdown\Converter;

use Share_On_Mastodon\League\HTMLToMarkdown\ElementInterface;
interface ConverterInterface
{
    public function convert(ElementInterface $element) : string;
    /**
     * @return string[]
     */
    public function getSupportedTags() : array;
}
