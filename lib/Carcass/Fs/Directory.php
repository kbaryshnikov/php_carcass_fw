<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Fs;

/**
 * Directory tools
 * @package Carcass\Fs
 */
class Directory {

    const DEFAULT_MKDIR_PERM = 0755;

    /**
     * @param string $dir
     * @return bool
     */
    public static function isReadable($dir) {
        return file_exists($dir) && is_dir($dir) && is_readable($dir);
    }

    /**
     * @param string $from_dir
     * @param string $to_dir
     * @param int|null $mkdir_perm, DEFAULT_MKDIR_PERM if null
     * @param bool $find_unused_directory
     * @param null $result_dir full pathname of final target directory returned by reference
     * @return bool
     * @throws \RuntimeException
     */
    public static function move($from_dir, $to_dir, $mkdir_perm = null, $find_unused_directory = false, &$result_dir = null) {
        if (empty($mkdir_perm)) {
            $mkdir_perm = self::DEFAULT_MKDIR_PERM;
        }
        $result_dir = self::findFullPathToDestinationDirectory($from_dir, $to_dir, $mkdir_perm, $find_unused_directory);
        if (!rename($from_dir, $result_dir)) {
            throw new \RuntimeException("Failed to move '$from_dir'->'$result_dir'");
        }
        return true;
    }

    /**
     * @param string $to_dir
     * @param int|null $mkdir_perm, DEFAULT_MKDIR_PERM if null
     * @return bool
     * @throws \RuntimeException
     */
    public static function mkdirIfNotExists($to_dir, $mkdir_perm = null) {
        if (empty($mkdir_perm)) {
            $mkdir_perm = self::DEFAULT_MKDIR_PERM;
        }
        if (!file_exists($to_dir)) {
            if (!mkdir($to_dir, $mkdir_perm, true)) {
                throw new \RuntimeException("Cannot mkdir '$to_dir'");
            }
        } else {
            if (!is_dir($to_dir)) {
                throw new \RuntimeException("'$to_dir' exists but is not a directory");
            }
        }
        return true;
    }

    /**
     * @param $from_dir
     * @param $to_dir
     * @param int|null $mkdir_perm, DEFAULT_MKDIR_PERM if null
     * @param bool $find_unused_directory
     * @return string
     * @throws \RuntimeException
     */
    protected static function findFullPathToDestinationDirectory($from_dir, $to_dir, $mkdir_perm = null, $find_unused_directory = false) {
        if (!file_exists($to_dir)) {
            self::mkdirIfNotExists(dirname($to_dir), $mkdir_perm);
            return $to_dir;
        } elseif (!is_dir($to_dir)) {
            throw new \RuntimeException("'$to_dir' exists but is not a directory");
        } else {
            $base_from_dir = basename($from_dir);
            $to_dir .= '/' . $base_from_dir;
            if (file_exists($to_dir)) {
                if ($find_unused_directory) {
                    $idx = 0;
                    $separator = is_string($find_unused_directory) ? $find_unused_directory : '';
                    do {
                        $idx++;
                        $candidate = $to_dir . $separator . $idx;
                    } while (file_exists($candidate));
                    $to_dir = $candidate;
                } else {
                    throw new \RuntimeException("'$to_dir' already exists");
                }
            }
            return $to_dir;
        }
    }

    /**
     * @param string $dir
     */
    public static function deleteRecursively($dir) {
        $Iterator = new \RecursiveDirectoryIterator($dir);
        foreach (new \RecursiveIteratorIterator($Iterator, \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

}
