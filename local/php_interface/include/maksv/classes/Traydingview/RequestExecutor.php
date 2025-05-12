<?php
namespace Maksv\Traydingview;

class RequestExecutor
{
    protected string $pythonPath = '/usr/bin/python3';
    protected string $othersScriptPath = '/home/c/cz06737izol/crypto/public_html/local/php_interface/include/maksv/classes/Traydingview/py/requestOthers.py';

    /**
     * Выполняет Python-скрипт и возвращает true при успешном выполнении, иначе false
     */
    public function execute($symbol = "others")
    {
        $scriptPath = $this->othersScriptPath;
        switch ($symbol) {
            case 'others':
                $scriptPath = $this->othersScriptPath;
                break;
            case 'total3':
                //$scriptPath = $this->othersScriptPath;
                break;
        }

        $cmd = escapeshellcmd("{$this->pythonPath} {$scriptPath}");
        $output = [];
        $returnVar = null;
        exec($cmd, $output, $returnVar);
        // Последняя строка выводимого содержит 'OK' при успехе
        if ($returnVar === 0 && isset($output[count($output)-1]) && trim($output[count($output)-1]) === 'OK') {
            return true;
        }
        return false;
    }
}