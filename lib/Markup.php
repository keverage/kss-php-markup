<?php

namespace Kss;

/**
 * Class Markup
 *
 * @package Kss
 */
class Markup
{
    /**
     * @var \StdClass
     */
    protected $options;

    /**
     * @var null
     */
    protected $url = null;

    /**
     * @var null
     */
    protected $selector = null;

    /**
     * @var
     */
    protected $filter;

    /**
     * @var null
     */
    protected $markup = null;

    /**
     * @var array
     */
    protected $PHPHtmlParserOptions = array(
        'preserveLineBreaks' => true
    );

    /**
     * @var \Kss\Cache
     */
    protected $cache;

    /**
     * Markup constructor.
     *
     * @param \Kss\Section $section
     * @param \StdClass    $options
     * @return $this
     */
    public function __construct(\Kss\Section $section, \StdClass $options = null)
    {
        if (!class_exists('PHPHtmlParser\Dom')) {
            return false;
        }

        // Set default markup
        $this->set($section->getMarkup());

        // Options
        $this->options = new \StdClass;
        $this->options->cache = false;
        $this->options->modifier = '$modifierClass';
        $this->options->urlPrefix = null;

        if (!empty($options)) {
            $this->setOptions($options);
        }

        // Cache
        if ($this->options->cache) {
            $this->cache = new Cache;
        }

        // Auto markup
        if ($this->isUrl()) {
            // Filter
            $this->filter = new \StdClass;
            $this->filter->length = 2;

            // Parse and set
            $this->parse()->setFromUrl();
        }

        // Reset cache
        if (!$this->options->cache) {
            $this->flushcache();
        }

        return $this;
    }

    /**
     * Set options
     *
     * @param \stdClass $options
     * @return $this
     */
    public function setOptions(\stdClass $options)
    {
        foreach ($options as $key => $value) {
            if (property_exists($this->options, $key)) {
                $this->options->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Get all options
     *
     * @return \StdClass
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get one option
     *
     * @param string $option Property key option
     * @return mixed|bool
     */
    public function getOption($option)
    {
        if (!empty($this->options->{$option})) {
            return $this->options->{$option};
        }

        return false;
    }

    /**
     * Parse markup
     *
     * @return $this
     */
    public function parse()
    {
        $matches = [];
        preg_match_all('@url\((.*)\)->find\(([-\.#\w]+)\)(?:->filter\(([-\.#\w]+)(?:,(.+))?\))?@', $this->get(), $matches);

        $this->url = $this->getOption('urlPrefix');
        if (!empty($matches[1][0])) {
            $this->url .= trim($matches[1][0], ' \'"');
        }
        if (!empty($matches[2][0])) {
            $this->selector = trim($matches[2][0], ' \'"');
        }
        if (!empty($matches[3][0])) {
            $this->filter->selector = trim($matches[3][0], ' \'"');
        }
        if (!empty($matches[4][0])) {
            $this->filter->length = (int) trim($matches[4][0]);
        }

        return $this;
    }

    /**
     * Set markup from parsed result
     *
     * @return $this
     */
    public function setFromUrl()
    {
        // Cache?
        $cacheRebuild = true;
        if ($this->options->cache) {
            $cacheKey = md5($this->get());
            $cacheData = $this->cache->get($cacheKey);

            if (!empty($cacheData)) {
                $cacheRebuild = false;
                $this->set($cacheData);
            }
        }

        // Get dom
        if ($cacheRebuild) {
            $dom = new \PHPHtmlParser\Dom;
            $dom->load($this->url, $this->PHPHtmlParserOptions);
            $element = $dom->find($this->selector)[0];

            if (!empty($element)) {
                // Filter?
                if (!empty($this->filter->selector)) {
                    $newElement = new \PHPHtmlParser\Dom;
                    $newElement->load($element->outerHtml, $this->PHPHtmlParserOptions);
                    $filter = $newElement->find($this->filter->selector);

                    if (!empty($filter)) {
                        $i = 0;
                        foreach ($filter as $item) {
                            if ($i >= $this->filter->length) {
                                $item->delete();
                            }
                            $i++;
                        }
                    }
                    unset($filter);

                    $html = $newElement->outerHtml;

                } else {
                    $html = $element->outerHtml;
                }

                $this->set($html);

                if ($this->options->cache && !empty($cacheKey)) {
                    $this->cache->set($cacheKey, $html);
                }
            }
        }

        return $this;
    }

    /**
     * Get markup by url?
     *
     * @return bool
     */
    public function isUrl()
    {
        return (substr($this->get(), 0, 4) === 'url(');
    }

    /**
     * Set markup
     *
     * @param string $markup
     * @return $this
     */
    public function set($markup)
    {
        $this->markup = $markup;

        return $this;
    }

    /**
     * Get markup
     *
     * @return string
     */
    public function get()
    {
        return $this->markup;
    }

    /**
     * Get markup without modifier option
     *
     * @param string $replacement Replacement for modifier option
     * @return string
     */
    public function getNormal($replacement = '')
    {
        return str_replace($this->getOption('modifier'), $replacement, $this->get());
    }

    /**
     * Flush markup cache
     *
     * @return $this
     */
    public function flushcache()
    {
        if (isset($this->cache)) {
            $this->cache->clean();
        }

        return $this;
    }
}