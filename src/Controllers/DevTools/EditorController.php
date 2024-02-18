<?php

namespace Slowlyo\OwlAdmin\Controllers\DevTools;

use Illuminate\Support\Arr;
use Slowlyo\OwlAdmin\Renderers\RendererMap;
use Slowlyo\OwlAdmin\Controllers\AdminController;

class EditorController extends AdminController
{
    public function index()
    {
        $schema = $this->parse(request('schema'));

        return $this->response()->success(compact('schema'));
    }

    public function parse($json, $indentLevel = 0)
    {
        $indent = str_repeat("    ", $indentLevel); // 4个空格作为缩进单位
        $code = '';
        $map = RendererMap::$map;
        $mapKeys = array_keys($map);

        if ($json['type'] ?? null) {
            if (in_array($json['type'], $mapKeys)) {
                $className = str_replace('Slowlyo\\OwlAdmin\\Renderers\\', '', $map[$json['type']]);
                $code .= $indent . sprintf(PHP_EOL . $indent . 'amis()->%s()', $className);
            } else {
                $code .= $indent . sprintf(PHP_EOL . $indent . 'amis(\'%s\')', $json['type']);
            }
            foreach ($json as $key => $value) {
                if ($key == 'type' || $key == 'id') {
                    continue;
                }
                if (is_array($value)) {
                    $code .= sprintf("%s->%s(", $indent, $key) . $this->parse($value, $indentLevel + 1) . ')';
                } else {
                    $code .= sprintf("%s->%s('%s')", "", $key, $this->escape($value));
                }
            }
        } else {
            $code .= $indent . '[';
            foreach ($json as $key => $value) {
                if (is_array($value)) {
                    if (Arr::isList($json)) {
                        $code .= sprintf("%s%s,", $indent . "    ", $this->parse($value, $indentLevel + 1));
                    } else {
                        $code .= sprintf("%s'%s' => %s,", $indent . "    ", $key, $this->parse($value, $indentLevel + 1));
                    }
                } else {
                    $code .= sprintf("%s'%s' => '%s',\n", $indent . "    ", $key, $this->escape($value));
                }
            }
            $code .= "\n" . $indent . ']';
        }
        return $code;
    }

    /**
     * 转义单引号
     *
     * @param $string
     *
     * @return string|string[]
     */
    public function escape($string)
    {
        return str_replace("'", "\'", $string);
    }
}
