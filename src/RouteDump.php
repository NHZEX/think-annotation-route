<?php
declare(strict_types=1);

namespace Zxin\Think\Route;

use Brick\VarExporter\VarExporter;
use Zxin\Think\Annotation\DumpValue;
use function app;

class RouteDump extends DumpValue
{
    public static function dump(): void
    {
        echo '====== RouteDump ======' . PHP_EOL;
        $path = RouteLoader::getDumpFilePath();
        (new self($path))->scanAnnotation();
        echo '========== DONE ==========' . PHP_EOL;
    }

    public function exportVar($data, $default = '[]')
    {
        return VarExporter::export($data);
    }

    public function scanAnnotation(): void
    {
        $rs    = new RouteScanning(app());
        $items = $rs->scan();

        $this->load();
        $this->save($items);
    }
}
