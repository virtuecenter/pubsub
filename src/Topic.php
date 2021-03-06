<?php
/**
 * Opine\Topic
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine\PubSub;

use ArrayObject;
use Exception;
use Opine\Interfaces\Topic as TopicInterface;
use Opine\Interfaces\Route as RouteInterface;

class Topic implements TopicInterface
{
    private $topics = [];
    private $route;
    private $model;

    public function __construct(RouteInterface $route, $model)
    {
        $this->route = $route;
        $this->model = $model;
    }

    public function cacheSet($cache)
    {
        if ($cache === false || !is_array($cache) || !isset($cache['topics']) || !is_array($cache['topics'])) {
            $this->topics = $this->model->readDiskCache();

            return;
        }
        $this->topics = (array) $cache['topics'];
    }

    /**
     * @codeCoverageIgnore
     */
    public function show()
    {
        foreach ($this->topics as $key => $value) {
            echo $key, "\n";
            foreach ($value as $call) {
                echo ' - ', $call, "\n";
            }
            echo "\n";
        }
    }

    public function subscribe($topic, $callback)
    {
        if (!is_string($callback) || substr_count($callback, '@') != 1) {
            throw new Exception('Callback must be a string expressed in the format: service@method');
        }
        if (!isset($this->topics[$topic])) {
            $this->topics[$topic] = [];
        }
        $this->topics[$topic][] = $callback;
    }

    public function publish($topic, ArrayObject $context)
    {
        if (!isset($this->topics[$topic]) || !is_array($this->topics[$topic]) || empty($this->topics[$topic])) {
            return;
        }
        foreach ($this->topics[$topic] as $subscriber) {
            if (!is_string($subscriber)) {
                continue;
            }
            if (substr_count($subscriber, '@') != 1) {
                continue;
            }
            $response = $this->route->serviceMethod($subscriber, $context);
            if ($response === false) {
                break;
            }
        }
    }
}
