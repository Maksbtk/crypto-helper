<?php
namespace Maksv\Traydingview;

class RequestExecutor
{
    protected string $pythonPath         = '/usr/bin/python3';
    protected string $othersScriptPath  = '/home/c/cz06737izol/crypto/public_html/local/php_interface/include/maksv/classes/Traydingview/py/requestOthers.py';
    protected string $total3ScriptPath  = '/home/c/cz06737izol/crypto/public_html/local/php_interface/include/maksv/classes/Traydingview/py/requestTotal3.py';

    /**
     * Выполняет Python-скрипт и возвращает true при успешном выполнении, иначе false
     */
    public function execute($symbol = "others")
    {
        switch ($symbol) {
            case 'total3':
                $scriptPath = $this->total3ScriptPath;
                break;
            case 'others':
            default:
                $scriptPath = $this->othersScriptPath;
                break;
        }

        // Префиксируем команду экспортом нужных переменных
        $cmd = sprintf(
            'OPENBLAS_NUM_THREADS=1 OMP_NUM_THREADS=1 %s %s',
            escapeshellcmd($this->pythonPath),
            escapeshellarg($scriptPath)
        );

        $output    = [];
        $returnVar = null;
        exec($cmd, $output, $returnVar);

        // Проверяем код возврата и наличие 'OK' в последней строке
        if ($returnVar === 0
            && !empty($output)
            && trim(end($output)) === 'OK'
        ) {
            return true;
        }

        return false;
    }
}
