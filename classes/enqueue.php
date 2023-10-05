<?php

namespace Anstech\Template;

use ErrorException;
use Fuel\Core\Arr;
use Fuel\Core\Config;
use Parser\View;

/**
 * Script / style passed should be in the format:
 *
 * array('url' => '...', 'script' => '')
 * array('url' => '...', 'style' => '')
 *
 * Where url and script/style are optional
 */
class Enqueue
{
    private const POSITIONS = [
        'head',
        'default',
    ];

    protected static $queued_scripts = [];
    protected static $queued_styles = [];

    /**
     * Queue a javascript file for later rendering
     *
     * @param array $script
     * @param string $position
     */
    public static function enqueueScript($script, $position = 'default')
    {
        static::checkPosition($position);
        static::$queued_scripts[$position][] = $script;
    }


    public static function enqueueScripts($scripts, $position = 'default')
    {
        static::checkPosition($position);
        foreach ($scripts as $script) {
            static::enqueueScript($script, $position);
        }
    }


    /**
     * Queue a stylesheet for later rendering
     *
     * @param array $style
     * @param string $position
     */
    public static function enqueueStyle($style, $position = 'default')
    {
        static::checkPosition($position);
        static::$queued_styles[$position][] = $style;
    }


    public static function enqueueStyles($styles, $position = 'default')
    {
        static::checkPosition($position);
        foreach ($styles as $style) {
            static::enqueueStyle($style, $position);
        }
    }


    /**
     * Return the script(s) to be rendered in the requested position
     *
     * @param string    $position   Position
     * @param bool      $render     Render scripts
     * @return array
     */
    public static function scripts($position = 'default', $render = false)
    {
        static::checkPosition($position);
        if (isset(static::$queued_scripts[$position])) {
            $scripts = Arr::unique(static::$queued_scripts[$position]);

            if ($render) {
                echo View::forge('scripts.mustache', ['scripts' => $scripts], false)->render();
            }

            return $scripts;
        }

        return [];
    }


    /**
     * Return the style(s) to be rendered in the requested position
     *
     * @param string    $position   Position
     * @param bool      $render     Render styles
     * @return array
     */
    public static function styles($position = 'default', $render = false)
    {
        static::checkPosition($position);
        if (isset(static::$queued_styles[$position])) {
            $styles = Arr::unique(static::$queued_styles[$position]);

            if ($render) {
                echo View::forge('styles.mustache', ['styles' => $styles], false)->render();
            }

            return $styles;
        }

        return [];
    }


    protected static function plugin($plugin)
    {
        $plugins = Config::load('plugins', true);

        if (isset($plugins[$plugin])) {
            return $plugins[$plugin];
        }

        return false;
    }


    public static function enqueuePlugins($plugins)
    {
        if (is_string($plugins)) {
            $plugins = [$plugins];
        }

        foreach ($plugins as $plugin) {
            if ($plugin = static::plugin($plugin)) {
                // Enqueue plugin styles
                if (isset($plugin['styles']) && ($styles = $plugin['styles'])) {
                    static::enqueueStyles($styles);
                }

                // Enqueue plugin scripts
                if (isset($plugin['scripts']) && ($scripts = $plugin['scripts'])) {
                    static::enqueueScripts($scripts);
                }
            }
        }
    }

    protected static function checkPosition($position)
    {
        if (! in_array($position, static::POSITIONS)) {
            throw new ErrorException('Provided position: ' . $position . ' is not a valid option (' . implode(', ', static::POSITIONS) . ')');
        }
    }
}
