<?php

namespace Carcass\Tools;

use Carcass\Corelib;
use Carcass\Fs;
use Carcass\Less;

class CompileLessScript extends Controller {

    protected $verbose = false;

    public function actionDefault(Corelib\Hash $Args) {
        $this->verbose = (bool)$Args->get('v');

        $in = $Args->get(1);
        if (!$in) {
            $input_dir = getcwd();
            $filter_mask = '*.less';
        } else {
            $input_dir = dirname($in);
            if (!$input_dir || '.' === $input_dir) {
                $input_dir = getcwd();
            }

            $filter_mask = basename($in);
            if (!$filter_mask) {
                $filter_mask = '*.less';
            }

        }

        $css_files = (new Fs\Iterator($input_dir))
            ->setFilterMask($filter_mask)
            ->setIncludeFiles()
            ->setIncludeFolders(false)
            ->setIncludeHidden(false)
            ->setRecurse()
            ->setSort()
            ->setReturnFullPath()
            ->exportArray();

        if (!$css_files) {
            $this->say('No files found');
            return 0;
        }

        $output_filename = $Args->get('o');
        if ($output_filename) {
            $oh = fopen($output_filename, 'a+');
            flock($oh, LOCK_EX);
            ftruncate($oh, 0);
            fseek($oh, 0);
        } else {
            $oh = fopen('php://output', 'w');
        }

        $LessCompiler = new Less\Compiler();
        $LessCompiler->setImportDir($input_dir);
        $LessCompiler->setFormatter($Args->get('f', 'compressed'));

        foreach ($css_files as $css_file) {
            $compiled_string = $LessCompiler->compileFile($css_file);
            fwrite($oh, $compiled_string);
        }

        if ($output_filename) {
            flock($oh, LOCK_UN);
        }
        fclose($oh);
        return 0;
    }

    protected function say($msg) {
        if ($this->verbose) {
            $this->Response->writeErrorLn($msg);
        }
    }

    protected function getHelp() {
        return [
            '<filename>' => 'LESS filename or file mask. Required',
            '-o' => 'Output filename, default = print to STDOUT',
            '-f' => 'Formatter name, default = compressed',
            '-v' => 'Be verbose',
        ];
    }

}
