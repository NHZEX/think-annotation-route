<?php

declare(strict_types=1);

namespace Zxin\Think\Route;

use Brick\VarExporter\VarExporter;
use think\App;
use Zxin\Think\Annotation\DumpValue;

class RouteDump extends DumpValue
{
    public static function dump(): void
    {
        echo '====== RouteDump ======' . PHP_EOL;
        $path = RouteLoader::getDumpFilePath();
        (new self($path))->scanAnnotation();
        echo '========== DONE ==========' . PHP_EOL;
    }

    public function exportVar($data, string $default = '[]'): string
    {
        return VarExporter::export($data);
    }

    public function saveData(array $items)
    {
        $this->load();
        $this->save($items);
    }

    public function scanAnnotation(): void
    {
        $rs    = new RouteScanning(App::getInstance());
        $items = $rs->scan();

        $this->load();
        $this->save($items);
    }
}
